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
        $this->version = '1.3.0';
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
        include(dirname(__FILE__).'/sql/install.php');
        
        // Generate a secure random token for CRON job
        $token = bin2hex(random_bytes(16)); // 32 characters long
        Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN', $token);
        
        // Set default batch size for processing
        Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        
        return parent::install() &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->installTab('AdminAttributeFeatureConnector', 'Attribute-Feature Connector', 'AdminParentModulesSf') &&
            $this->installTab('AdminAttributeFeatureAnalytics', 'Attribute-Feature Analytics', 'AdminParentModulesSf') &&
            $this->installTab('AdminCategoryFeatureMapping', 'Category-Feature Mapping', 'AdminParentModulesSf');
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        
        // Remove configuration values
        Configuration::deleteByName('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN');
        Configuration::deleteByName('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE');
        
        return parent::uninstall() &&
            $this->uninstallTab('AdminAttributeFeatureConnector') &&
            $this->uninstallTab('AdminAttributeFeatureAnalytics') &&
            $this->uninstallTab('AdminCategoryFeatureMapping');
    }

    protected function installTab($className, $tabName, $parentClassName)
    {
        $tab = new Tab();
        $tab->active = 1;
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
        if ($controller === 'AdminAttributeFeatureConnector') {
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        } elseif ($controller === 'AdminAttributeFeatureAnalytics') {
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
            $this->context->controller->addJS('https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js');
        } elseif ($controller === 'AdminCategoryFeatureMapping') {
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        }
    }
    
    /**
     * Upon module update
     */
    public function upgrade($old_version, $new_version)
    {
        // Version-specific upgrades
        if (version_compare($old_version, '1.2.0', '<')) {
            // Execute update SQL for version 1.2.0
            include(dirname(__FILE__).'/sql/update-1.2.0.php');
            
            // Install the new admin tab for analytics
            $this->installTab('AdminAttributeFeatureAnalytics', 'Attribute-Feature Analytics', 'AdminParentModulesSf');
        }
        
        if (version_compare($old_version, '1.3.0', '<')) {
            // Execute update SQL for version 1.3.0
            include(dirname(__FILE__).'/sql/update-1.3.0.php');
            
            // Install the new admin tab for category feature mapping
            $this->installTab('AdminCategoryFeatureMapping', 'Category-Feature Mapping', 'AdminParentModulesSf');
        }
        
        return true;
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
            $memory_usage = memory_get_peak_usage(true);
            
            Db::getInstance()->insert('attribute_feature_performance_log', [
                'operation' => pSQL($operation),
                'id_mapping' => $id_mapping ? (int)$id_mapping : null,
                'products_processed' => (int)$products_processed,
                'products_updated' => (int)$products_updated,
                'execution_time' => (float)$execution_time,
                'memory_usage' => (int)$memory_usage,
                'batch_size' => (int)$batch_size,
                'date_add' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get all feature values that are already mapped (to hide from dropdown)
     */
    public static function getMappedFeatureValues()
    {
        $query = new DbQuery();
        $query->select('DISTINCT id_feature_value')
              ->from('attribute_feature_mapping');
              
        $result = Db::getInstance()->executeS($query);
        
        $mappedValues = [];
        if ($result) {
            foreach ($result as $row) {
                $mappedValues[] = (int)$row['id_feature_value'];
            }
        }
        
        return $mappedValues;
    }
    
    /**
     * Get all category mapped feature values
     */
    public static function getCategoryMappedFeatureValues()
    {
        $query = new DbQuery();
        $query->select('DISTINCT id_feature_value')
              ->from('category_feature_mapping');
              
        $result = Db::getInstance()->executeS($query);
        
        $mappedValues = [];
        if ($result) {
            foreach ($result as $row) {
                $mappedValues[] = (int)$row['id_feature_value'];
            }
        }
        
        return $mappedValues;
    }
}