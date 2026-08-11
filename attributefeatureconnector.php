<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AttributeFeatureConnector extends Module
{
    public function __construct()
    {
        $this->name = 'attributefeatureconnector';
        $this->tab = 'administration';
        $this->version = '1.4.0';
        $this->author = 'Dainius';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Attribute-Feature Connector');
        $this->description = $this->l('Automatically assign features to products based on their attributes or categories');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        include(dirname(__FILE__).'/sql/install.php');

        $token = bin2hex(random_bytes(16));
        Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN', $token);
        Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_REALTIME', 0);

        return parent::install() &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->registerHook('actionObjectProductAddAfter') &&
            $this->registerHook('actionObjectProductUpdateAfter') &&
            $this->registerHook('actionProductAttributeUpdate') &&
            $this->registerHook('actionCategoryProductAdd') &&
            $this->registerHook('actionCategoryProductDelete') &&
            $this->installTab('AdminAttributeFeatureConnector', 'Attribute-Feature Connector', 'AdminParentModulesSf') &&
            $this->installTab('AdminAttributeFeatureAnalytics', 'Attribute-Feature Analytics', 'AdminParentModulesSf') &&
            $this->installTab('AdminCategoryFeatureMapping', 'Category-Feature Mapping', 'AdminParentModulesSf');
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');

        Configuration::deleteByName('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN');
        Configuration::deleteByName('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE');
        Configuration::deleteByName('ATTRIBUTE_FEATURE_CONNECTOR_REALTIME');

        return parent::uninstall() &&
            $this->uninstallTab('AdminAttributeFeatureConnector') &&
            $this->uninstallTab('AdminAttributeFeatureAnalytics') &&
            $this->uninstallTab('AdminCategoryFeatureMapping');
    }

    protected function installTab($className, $tabName, $parentClassName)
    {
        $tab = new Tab();
        $tab->active = 0;
        $tab->class_name = $className;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }
        $tab->id_parent = (int)Tab::getIdFromClassName($parentClassName);
        $tab->module = $this->name;

        return $tab->add();
    }

    protected function uninstallTab($className)
    {
        $id_tab = (int)Tab::getIdFromClassName($className);
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }

        return true;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminAttributeFeatureConnector'));
    }

    public function hookActionAdminControllerSetMedia()
    {
        $controller = Tools::getValue('controller');
        if ($controller === 'AdminAttributeFeatureConnector' || $controller === 'AdminCategoryFeatureMapping') {
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        } elseif ($controller === 'AdminAttributeFeatureAnalytics') {
            $this->context->controller->addJS($this->_path.'views/js/vendor/chart.min.js');
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        }
    }

    // ===== Real-time hooks =====

    public function hookActionObjectProductAddAfter($params)
    {
        if (!Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_REALTIME')) {
            return;
        }
        $product = $params['object'];
        self::applyAttributeMappingsToProduct((int)$product->id);
        self::applyCategoryMappingsToProduct((int)$product->id);
    }

    public function hookActionObjectProductUpdateAfter($params)
    {
        if (!Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_REALTIME')) {
            return;
        }
        $product = $params['object'];
        self::applyAttributeMappingsToProduct((int)$product->id);
        self::applyCategoryMappingsToProduct((int)$product->id);
    }

    public function hookActionProductAttributeUpdate($params)
    {
        if (!Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_REALTIME')) {
            return;
        }
        $id_product_attribute = (int)$params['id_product_attribute'];
        $id_product = (int)Db::getInstance()->getValue(
            'SELECT id_product FROM `' . _DB_PREFIX_ . 'product_attribute`
             WHERE id_product_attribute = ' . $id_product_attribute
        );
        if ($id_product) {
            self::applyAttributeMappingsToProduct($id_product);
        }
    }

    public function hookActionCategoryProductAdd($params)
    {
        if (!Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_REALTIME')) {
            return;
        }
        $id_product = (int)$params['id_product'];
        $id_category = (int)$params['id_category'];
        self::applyCategoryMappingsToProduct($id_product, $id_category);
    }

    public function hookActionCategoryProductDelete($params)
    {
        // Features are not auto-removed when a product leaves a category —
        // the feature may still be valid via another mapping. Use Undo Mapping manually if needed.
    }

    // ===== Shared static methods used by controllers and cron =====

    /**
     * Apply all active attribute mappings to a single product (used by real-time hooks)
     */
    public static function applyAttributeMappingsToProduct($id_product)
    {
        $mappings = Db::getInstance()->executeS(
            'SELECT afm.id_feature_value, fv.id_feature,
                    GROUP_CONCAT(afma.id_attribute) AS attribute_ids
             FROM `' . _DB_PREFIX_ . 'attribute_feature_mapping` afm
             JOIN `' . _DB_PREFIX_ . 'attribute_feature_mapping_attributes` afma
                   ON afm.id_mapping = afma.id_mapping
             JOIN `' . _DB_PREFIX_ . 'feature_value` fv
                   ON afm.id_feature_value = fv.id_feature_value
             GROUP BY afm.id_mapping'
        );

        if (!$mappings) {
            return;
        }

        foreach ($mappings as $mapping) {
            $attr_list = implode(',', array_map('intval', explode(',', $mapping['attribute_ids'])));
            $has_attribute = (int)Db::getInstance()->getValue(
                'SELECT COUNT(*)
                 FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                 JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
                       ON pa.id_product_attribute = pac.id_product_attribute
                 WHERE pa.id_product = ' . (int)$id_product . '
                   AND pac.id_attribute IN (' . $attr_list . ')'
            );
            if ($has_attribute) {
                Db::getInstance()->execute(
                    'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'feature_product`
                         (id_feature, id_product, id_feature_value)
                     VALUES (' . (int)$mapping['id_feature'] . ', ' . (int)$id_product . ', ' . (int)$mapping['id_feature_value'] . ')'
                );
            }
        }
    }

    /**
     * Apply all active category mappings to a single product (used by real-time hooks)
     * Pass $id_category_filter to process only mappings for a specific category
     */
    public static function applyCategoryMappingsToProduct($id_product, $id_category_filter = null)
    {
        $where = $id_category_filter !== null
            ? ' WHERE cfm.id_category = ' . (int)$id_category_filter
            : '';

        $mappings = Db::getInstance()->executeS(
            'SELECT cfm.id_feature_value, cfm.id_category, cfm.include_subcategories, fv.id_feature
             FROM `' . _DB_PREFIX_ . 'category_feature_mapping` cfm
             JOIN `' . _DB_PREFIX_ . 'feature_value` fv
                   ON cfm.id_feature_value = fv.id_feature_value' . $where
        );

        if (!$mappings) {
            return;
        }

        $product_categories = Db::getInstance()->executeS(
            'SELECT id_category FROM `' . _DB_PREFIX_ . 'category_product`
             WHERE id_product = ' . (int)$id_product
        );

        if (!$product_categories) {
            return;
        }

        $product_category_ids = array_column($product_categories, 'id_category');

        foreach ($mappings as $mapping) {
            $category_ids = self::getCategoryIds((int)$mapping['id_category'], (bool)$mapping['include_subcategories']);
            if (array_intersect($product_category_ids, $category_ids)) {
                Db::getInstance()->execute(
                    'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'feature_product`
                         (id_feature, id_product, id_feature_value)
                     VALUES (' . (int)$mapping['id_feature'] . ', ' . (int)$id_product . ', ' . (int)$mapping['id_feature_value'] . ')'
                );
            }
        }
    }

    /**
     * Bulk assign a feature to a batch of products using INSERT IGNORE.
     * Eliminates N+1 queries — replaces the per-product SELECT COUNT + INSERT pattern.
     * Returns number of rows actually inserted.
     */
    public static function assignFeatureToProducts($id_feature, $id_feature_value, array $product_ids)
    {
        if (empty($product_ids)) {
            return 0;
        }
        $id_list = implode(',', array_map('intval', $product_ids));
        Db::getInstance()->execute(
            'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'feature_product` (id_feature, id_product, id_feature_value)
             SELECT ' . (int)$id_feature . ', id_product, ' . (int)$id_feature_value . '
             FROM `' . _DB_PREFIX_ . 'product`
             WHERE id_product IN (' . $id_list . ')'
        );
        return (int)Db::getInstance()->Affected_Rows();
    }

    /**
     * Bulk remove a feature from a batch of products.
     * Returns number of rows deleted.
     */
    public static function removeFeatureFromProducts($id_feature, $id_feature_value, array $product_ids)
    {
        if (empty($product_ids)) {
            return 0;
        }
        $id_list = implode(',', array_map('intval', $product_ids));
        Db::getInstance()->delete(
            'feature_product',
            'id_feature = ' . (int)$id_feature . '
             AND id_feature_value = ' . (int)$id_feature_value . '
             AND id_product IN (' . $id_list . ')'
        );
        return (int)Db::getInstance()->Affected_Rows();
    }

    /**
     * Get an array of category IDs, optionally including all descendant subcategories.
     * Uses iterative BFS to avoid deep recursion on large trees.
     */
    public static function getCategoryIds($id_category, $include_subcategories = false)
    {
        $ids = [(int)$id_category];

        if (!$include_subcategories) {
            return $ids;
        }

        $pending = [(int)$id_category];
        while (!empty($pending)) {
            $parent_list = implode(',', $pending);
            $children = Db::getInstance()->executeS(
                'SELECT id_category FROM `' . _DB_PREFIX_ . 'category`
                 WHERE id_parent IN (' . $parent_list . ')'
            );
            $pending = [];
            if ($children) {
                foreach ($children as $child) {
                    $child_id = (int)$child['id_category'];
                    if (!in_array($child_id, $ids)) {
                        $ids[] = $child_id;
                        $pending[] = $child_id;
                    }
                }
            }
        }

        return $ids;
    }

    /**
     * Log performance metrics
     */
    public static function logPerformance($operation, $id_mapping = null, $products_processed = 0, $products_updated = 0, $execution_time = 0, $batch_size = null)
    {
        if ($batch_size === null) {
            $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        }

        try {
            Db::getInstance()->insert('attribute_feature_performance_log', [
                'operation' => pSQL($operation),
                'id_mapping' => $id_mapping ? (int)$id_mapping : null,
                'products_processed' => (int)$products_processed,
                'products_updated' => (int)$products_updated,
                'execution_time' => (float)$execution_time,
                'memory_usage' => (int)memory_get_peak_usage(true),
                'batch_size' => (int)$batch_size,
                'date_add' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get all feature values already used in attribute mappings
     */
    public static function getMappedFeatureValues()
    {
        $result = Db::getInstance()->executeS(
            'SELECT DISTINCT id_feature_value FROM `' . _DB_PREFIX_ . 'attribute_feature_mapping`'
        );
        return $result ? array_column($result, 'id_feature_value') : [];
    }

    /**
     * Get all feature values already used in category mappings
     */
    public static function getCategoryMappedFeatureValues()
    {
        $result = Db::getInstance()->executeS(
            'SELECT DISTINCT id_feature_value FROM `' . _DB_PREFIX_ . 'category_feature_mapping`'
        );
        return $result ? array_column($result, 'id_feature_value') : [];
    }
}
