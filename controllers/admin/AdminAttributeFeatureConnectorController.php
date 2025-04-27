<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminAttributeFeatureConnectorController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        
        parent::__construct();
        
        $this->meta_title = $this->l('Attribute-Feature Connector');
        
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules'));
        }
    }
    
    public function initContent()
    {
        $this->content .= $this->renderConfigForm();
        
        parent::initContent();
    }
    
    public function renderConfigForm()
    {
        // Handle search queries
        $feature_search = Tools::getValue('feature_search', '');
        $attribute_search = Tools::getValue('attribute_search', '');
        
        // Get all already mapped feature values to exclude them from the list
        $mapped_feature_values = $this->getMappedFeatureValues();
        
        // Get all features with search filter
        $features = Feature::getFeatures($this->context->language->id);
        $feature_options = [];
        
        foreach ($features as $feature) {
            $feature_values = FeatureValue::getFeatureValuesWithLang(
                $this->context->language->id,
                $feature['id_feature']
            );
            
            foreach ($feature_values as $value) {
                // Search filter for features
                $feature_display = $feature['name'] . ' - ' . $value['value'];
                if (!empty($feature_search) && stripos($feature_display, $feature_search) === false) {
                    continue;
                }
                
                // Only add the feature value if it's not already mapped
                if (!in_array($value['id_feature_value'], $mapped_feature_values)) {
                    $feature_options[] = [
                        'id' => $value['id_feature_value'],
                        'name' => $feature_display
                    ];
                }
            }
        }
        
        // Get all attribute groups and attributes with search filter
        $attribute_groups = AttributeGroup::getAttributesGroups($this->context->language->id);
        $attribute_options = [];
        
        foreach ($attribute_groups as $group) {
            // Use direct DB query as a replacement for Attribute::getAttributes
            $attributes = Db::getInstance()->executeS('
                SELECT a.id_attribute, al.name
                FROM ' . _DB_PREFIX_ . 'attribute a
                LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al 
                    ON (a.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id . ')
                WHERE a.id_attribute_group = ' . (int)$group['id_attribute_group'] . '
                ORDER BY a.position ASC
            ');
            
            foreach ($attributes as $attribute) {
                $attribute_display = $group['name'] . ' - ' . $attribute['name'];
                
                // Search filter for attributes
                if (!empty($attribute_search) && stripos($attribute_display, $attribute_search) === false) {
                    continue;
                }
                
                $attribute_options[] = [
                    'id' => $attribute['id_attribute'],
                    'name' => $attribute_display
                ];
            }
        }
        
        // Pagination for mappings
        $page = (int)Tools::getValue('page', 1);
        $items_per_page = (int)Tools::getValue('items_per_page', 10);
        
        // Get total count of mappings
        $total_mappings = (int)Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM ' . _DB_PREFIX_ . 'attribute_feature_mapping
        ');
        
        $total_pages = ceil($total_mappings / $items_per_page);
        if ($page < 1) {
            $page = 1;
        } elseif ($page > $total_pages && $total_pages > 0) {
            $page = $total_pages;
        }
        
        // Get existing mappings with pagination
        $mappings = $this->getMappings($page, $items_per_page);
        
        // Get mapping being edited if applicable
        $mapping_to_edit = null;
        $edit_mapping_id = (int)Tools::getValue('edit_mapping');
        $selected_attributes = [];
        
        if ($edit_mapping_id) {
            foreach ($mappings as $mapping) {
                if ((int)$mapping['id_mapping'] === $edit_mapping_id) {
                    $mapping_to_edit = $mapping;
                    
                    // Get selected attributes for this mapping
                    $selected_attributes = $this->getAttributesForMapping($edit_mapping_id);
                    break;
                }
            }
            
            // If the mapping is not in the current page, fetch it separately
            if (!$mapping_to_edit) {
                $query = new DbQuery();
                $query->select('afm.*, fvl.value, f.name as feature_name, GROUP_CONCAT(al.name SEPARATOR ", ") as attributes')
                    ->from('attribute_feature_mapping', 'afm')
                    ->leftJoin('attribute_feature_mapping_attributes', 'afma', 'afm.id_mapping = afma.id_mapping')
                    ->leftJoin('feature_value_lang', 'fvl', 'afm.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id)
                    ->leftJoin('feature_value', 'fv', 'fvl.id_feature_value = fv.id_feature_value')
                    ->leftJoin('feature_lang', 'f', 'fv.id_feature = f.id_feature AND f.id_lang = ' . (int)$this->context->language->id)
                    ->leftJoin('attribute_lang', 'al', 'afma.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id)
                    ->where('afm.id_mapping = ' . (int)$edit_mapping_id)
                    ->groupBy('afm.id_mapping');
                
                $result = Db::getInstance()->executeS($query);
                if ($result && count($result) > 0) {
                    $mapping_to_edit = $result[0];
                    $selected_attributes = $this->getAttributesForMapping($edit_mapping_id);
                }
            }
        }
        
        // Preview products functionality
        $preview_mapping_id = (int)Tools::getValue('preview_mapping');
        $preview_products = [];
        $preview_attributes = [];
        $preview_feature = null;
        
        if ($preview_mapping_id) {
            $mapping_data = $this->getMappingById($preview_mapping_id);
            if ($mapping_data) {
                $preview_feature = [
                    'id_feature_value' => $mapping_data['id_feature_value'],
                    'feature_name' => $mapping_data['feature_name'],
                    'value' => $mapping_data['value']
                ];
                
                $preview_attributes = $this->getAttributesForMapping($preview_mapping_id);
                $preview_products = $this->getProductsForPreview($preview_attributes);
            }
        }
        
        $pagination_links = $this->generatePaginationLinks($page, $total_pages);
        
        // Get CRON token and URL
        $cron_token = Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN');
        $shop_domain = Context::getContext()->shop->getBaseURL(true);
        $cron_url = $shop_domain . 'index.php?fc=module&module=attributefeatureconnector&controller=cron&token=' . $cron_token;
        
        // Set batch size configuration
        $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        
        $this->context->smarty->assign([
            'feature_options' => $feature_options,
            'attribute_options' => $attribute_options,
            'mappings' => $mappings,
            'mapping_to_edit' => $mapping_to_edit,
            'selected_attributes' => $selected_attributes,
            'generate_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=generateAllFeatures',
            'generate_mapping_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=generateFeatures&id_mapping=',
            'undo_mapping_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=undoMapping&id_mapping=',
            'preview_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=previewMapping&preview_mapping=',
            'delete_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=deleteMapping',
            'edit_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=editMapping',
            'cancel_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector'),
            'current_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector'),
            'current_page' => $page,
            'total_pages' => $total_pages,
            'pagination_links' => $pagination_links,
            'items_per_page' => $items_per_page,
            'items_per_page_options' => [10, 20, 50, 100],
            'total_mappings' => $total_mappings,
            'cron_token' => $cron_token,
            'cron_url' => $cron_url,
            'feature_search' => $feature_search,
            'attribute_search' => $attribute_search,
            'batch_size' => $batch_size,
            'preview_products' => $preview_products,
            'preview_feature' => $preview_feature,
            'help_link' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=showHelp',
            'documentation_tab' => (Tools::getValue('action') === 'showHelp'),
            'preview_tab' => (Tools::isSubmit('preview_mapping')),
        ]);
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'attributefeatureconnector/views/templates/admin/configure.tpl');
    }
    
    /**
     * Get all feature values that have already been mapped
     */
    protected function getMappedFeatureValues()
    {
        $mapped_feature_values = [];
        
        $query = new DbQuery();
        $query->select('id_feature_value')
              ->from('attribute_feature_mapping');
        
        $result = Db::getInstance()->executeS($query);
        if ($result) {
            foreach ($result as $row) {
                $mapped_feature_values[] = (int)$row['id_feature_value'];
            }
        }
        
        return $mapped_feature_values;
    }
    
    /**
     * Get products that would be affected by a mapping (for preview)
     */
    protected function getProductsForPreview($attributes, $limit = 10)
    {
        if (empty($attributes)) {
            return [];
        }
        
        $products = [];
        
        // Get products with these attributes
        $sql = 'SELECT DISTINCT pa.id_product, pl.name as product_name, p.reference
                FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
                JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON pa.id_product_attribute = pac.id_product_attribute
                JOIN ' . _DB_PREFIX_ . 'product p ON p.id_product = pa.id_product
                JOIN ' . _DB_PREFIX_ . 'product_lang pl ON pl.id_product = p.id_product AND pl.id_lang = ' . (int)$this->context->language->id . '
                WHERE pac.id_attribute IN (' . implode(',', array_map('intval', $attributes)) . ')
                GROUP BY pa.id_product
                ORDER BY pl.name
                LIMIT ' . (int)$limit;
        
        $result = Db::getInstance()->executeS($sql);
        
        if ($result) {
            foreach ($result as $row) {
                $products[] = [
                    'id_product' => $row['id_product'],
                    'name' => $row['product_name'],
                    'reference' => $row['reference'],
                    'link' => $this->context->link->getAdminLink('AdminProducts', true, ['id_product' => $row['id_product'], 'updateproduct' => '1'])
                ];
            }
        }
        
        return $products;
    }
    
    /**
     * Get a specific mapping by ID
     */
    protected function getMappingById($id_mapping)
    {
        $query = new DbQuery();
        $query->select('afm.*, fvl.value, f.name as feature_name')
            ->from('attribute_feature_mapping', 'afm')
            ->leftJoin('feature_value_lang', 'fvl', 'afm.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('feature_value', 'fv', 'fvl.id_feature_value = fv.id_feature_value')
            ->leftJoin('feature_lang', 'f', 'fv.id_feature = f.id_feature AND f.id_lang = ' . (int)$this->context->language->id)
            ->where('afm.id_mapping = ' . (int)$id_mapping);
        
        return Db::getInstance()->getRow($query);
    }
    
    private function generatePaginationLinks($current_page, $total_pages)
    {
        $links = [];
        $base_url = $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&page=';
        
        // Previous link
        if ($current_page > 1) {
            $links['prev'] = $base_url . ($current_page - 1);
        }
        
        // Page links
        $links['pages'] = [];
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            $links['pages'][$i] = $base_url . $i;
        }
        
        // Next link
        if ($current_page < $total_pages) {
            $links['next'] = $base_url . ($current_page + 1);
        }
        
        return $links;
    }
    
    public function postProcess()
    {
        if (Tools::isSubmit('submitMapping')) {
            $id_feature_value = (int)Tools::getValue('id_feature_value');
            $selected_attributes = Tools::getValue('selected_attributes');
            
            if (!$id_feature_value || !is_array($selected_attributes) || empty($selected_attributes)) {
                $this->errors[] = $this->l('Please select a feature and at least one attribute');
                return;
            }
            
            $this->saveMapping($id_feature_value, $selected_attributes);
            $this->confirmations[] = $this->l('Mapping saved successfully');
        } elseif (Tools::isSubmit('submitEditMapping')) {
            $id_mapping = (int)Tools::getValue('id_mapping');
            $selected_attributes = Tools::getValue('selected_attributes');
            
            if (!$id_mapping || !is_array($selected_attributes) || empty($selected_attributes)) {
                $this->errors[] = $this->l('Please select at least one attribute');
                return;
            }
            
            $this->updateMapping($id_mapping, $selected_attributes);
            $this->confirmations[] = $this->l('Mapping updated successfully');
        } elseif (Tools::isSubmit('regenerate_cron_token')) {
            $new_token = bin2hex(random_bytes(16)); // 32 characters long
            Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN', $new_token);
            $this->confirmations[] = $this->l('CRON token regenerated successfully');
        } elseif (Tools::isSubmit('update_batch_size')) {
            $batch_size = (int)Tools::getValue('batch_size');
            if ($batch_size < 10) {
                $batch_size = 10;
            } elseif ($batch_size > 500) {
                $batch_size = 500;
            }
            Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', $batch_size);
            $this->confirmations[] = $this->l('Batch size updated successfully');
        } elseif (Tools::getValue('action') === 'generateAllFeatures') {
            $result = $this->generateAllFeatures();
            if ($result['success']) {
                $this->confirmations[] = sprintf($this->l('All features generated successfully. %d products updated.'), $result['updated']);
            } else {
                $this->errors[] = $this->l('Error generating features');
            }
        } elseif (Tools::getValue('action') === 'generateFeatures') {
            $id_mapping = (int)Tools::getValue('id_mapping');
            if ($id_mapping) {
                $result = $this->generateFeaturesForMapping($id_mapping);
                if ($result['success']) {
                    $this->confirmations[] = sprintf($this->l('Features for this mapping generated successfully. %d products updated.'), $result['updated']);
                } else {
                    $this->errors[] = $this->l('Error generating features for this mapping');
                }
            }
        } elseif (Tools::getValue('action') === 'undoMapping') {
            $id_mapping = (int)Tools::getValue('id_mapping');
            if ($id_mapping) {
                $result = $this->undoMapping($id_mapping);
                if ($result['success']) {
                    $this->confirmations[] = sprintf($this->l('Features for this mapping removed successfully. %d products updated.'), $result['updated']);
                } else {
                    $this->errors[] = $this->l('Error removing features for this mapping');
                }
            }
        } elseif (Tools::getValue('action') === 'deleteMapping') {
            $id_mapping = (int)Tools::getValue('id_mapping');
            if ($id_mapping) {
                $this->deleteMapping($id_mapping);
                $this->confirmations[] = $this->l('Mapping deleted successfully');
            }
        }
        
        parent::postProcess();
    }
    
    protected function getMappings($page = 1, $items_per_page = 10)
    {
        $mappings = [];
        $offset = ($page - 1) * $items_per_page;
        
        $query = new DbQuery();
        $query->select('afm.*, fvl.value, f.name as feature_name, GROUP_CONCAT(al.name SEPARATOR ", ") as attributes')
            ->from('attribute_feature_mapping', 'afm')
            ->leftJoin('attribute_feature_mapping_attributes', 'afma', 'afm.id_mapping = afma.id_mapping')
            ->leftJoin('feature_value_lang', 'fvl', 'afm.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('feature_value', 'fv', 'fvl.id_feature_value = fv.id_feature_value')
            ->leftJoin('feature_lang', 'f', 'fv.id_feature = f.id_feature AND f.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('attribute_lang', 'al', 'afma.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id)
            ->groupBy('afm.id_mapping')
            ->orderBy('afm.date_add DESC')
            ->limit($items_per_page, $offset);
        
        $result = Db::getInstance()->executeS($query);
        if ($result) {
            $mappings = $result;
        }
        
        return $mappings;
    }

    protected function getAttributesForMapping($id_mapping)
    {
        $attributes = [];
        $query = new DbQuery();
        $query->select('id_attribute')
              ->from('attribute_feature_mapping_attributes')
              ->where('id_mapping = ' . (int)$id_mapping);
        
        $result = Db::getInstance()->executeS($query);
        if ($result) {
            foreach ($result as $row) {
                $attributes[] = $row['id_attribute'];
            }
        }
        
        return $attributes;
    }
    
    protected function saveMapping($id_feature_value, $selected_attributes)
    {
        // Insert mapping
        $mapping = [
            'id_feature_value' => (int)$id_feature_value,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        
        Db::getInstance()->insert('attribute_feature_mapping', $mapping);
        $id_mapping = (int)Db::getInstance()->Insert_ID();
        
        // Insert attribute relations
        foreach ($selected_attributes as $id_attribute) {
            Db::getInstance()->insert('attribute_feature_mapping_attributes', [
                'id_mapping' => $id_mapping,
                'id_attribute' => (int)$id_attribute,
            ]);
        }
        
        return true;
    }

    protected function updateMapping($id_mapping, $selected_attributes)
    {
        // Update mapping date
        Db::getInstance()->update('attribute_feature_mapping', [
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_mapping = ' . (int)$id_mapping);
        
        // Delete old attribute relations
        Db::getInstance()->delete('attribute_feature_mapping_attributes', 'id_mapping = ' . (int)$id_mapping);
        
        // Insert new attribute relations
        foreach ($selected_attributes as $id_attribute) {
            Db::getInstance()->insert('attribute_feature_mapping_attributes', [
                'id_mapping' => $id_mapping,
                'id_attribute' => (int)$id_attribute,
            ]);
        }
        
        return true;
    }
    
    protected function deleteMapping($id_mapping)
    {
        Db::getInstance()->delete('attribute_feature_mapping', 'id_mapping = ' . (int)$id_mapping);
        Db::getInstance()->delete('attribute_feature_mapping_attributes', 'id_mapping = ' . (int)$id_mapping);
        
        return true;
    }
    
    public function generateAllFeatures()
    {
        $updated = 0;
        $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        
        // Get all mappings
        $mappings = [];
        $query = new DbQuery();
        $query->select('afm.id_mapping, afm.id_feature_value')
            ->from('attribute_feature_mapping', 'afm');
        
        $result = Db::getInstance()->executeS($query);
        
        if (!$result) {
            return ['success' => false, 'updated' => 0];
        }
        
        // Process each mapping
        foreach ($result as $mapping) {
            $attributes = $this->getAttributesForMapping($mapping['id_mapping']);
            $updated += $this->processFeaturesInBatches($mapping['id_feature_value'], $attributes, $batch_size);
        }
        
        return ['success' => true, 'updated' => $updated];
    }
    
    protected function generateFeaturesForMapping($id_mapping)
    {
        $updated = 0;
        $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        
        // Get mapping details
        $query = new DbQuery();
        $query->select('afm.id_feature_value')
            ->from('attribute_feature_mapping', 'afm')
            ->where('afm.id_mapping = ' . (int)$id_mapping);
        
        $mapping = Db::getInstance()->getRow($query);
        
        if (!$mapping) {
            return ['success' => false, 'updated' => 0];
        }
        
        // Get attributes for this mapping
        $attributes = $this->getAttributesForMapping($id_mapping);
        
        if (empty($attributes)) {
            return ['success' => false, 'updated' => 0];
        }
        
        // Process the mapping in batches
        $updated = $this->processFeaturesInBatches($mapping['id_feature_value'], $attributes, $batch_size);
        
        return ['success' => true, 'updated' => $updated];
    }
    
    protected function undoMapping($id_mapping)
    {
        $updated = 0;
        
        // Get mapping details
        $query = new DbQuery();
        $query->select('afm.id_feature_value')
            ->from('attribute_feature_mapping', 'afm')
            ->where('afm.id_mapping = ' . (int)$id_mapping);
        
        $result = Db::getInstance()->getRow($query);
        
        if (!$result) {
            return ['success' => false, 'updated' => 0];
        }
        
        $id_feature_value = $result['id_feature_value'];
        
        // Get feature information
        $feature_value = new FeatureValue($id_feature_value);
        $id_feature = $feature_value->id_feature;
        
        // Get products with this mapping
        $products_with_feature = Db::getInstance()->executeS('
            SELECT id_product
            FROM ' . _DB_PREFIX_ . 'feature_product
            WHERE id_feature = ' . (int)$id_feature . '
            AND id_feature_value = ' . (int)$id_feature_value
        );
        
        if (!$products_with_feature) {
            return ['success' => true, 'updated' => 0];
        }
        
        // Remove features from products
        foreach ($products_with_feature as $product) {
            $id_product = (int)$product['id_product'];
            
            Db::getInstance()->delete(
                'feature_product',
                'id_feature = ' . (int)$id_feature . ' 
                AND id_product = ' . (int)$id_product . ' 
                AND id_feature_value = ' . (int)$id_feature_value
            );
            
            $updated++;
        }
        
        return ['success' => true, 'updated' => $updated];
    }
    
    /**
     * Process features in batches to prevent timeout
     */
    protected function processFeaturesInBatches($id_feature_value, $attributes, $batch_size = 50)
    {
        $updated = 0;
        
        if (empty($attributes)) {
            return $updated;
        }
        
        // Get feature information
        $feature_value = new FeatureValue($id_feature_value);
        $id_feature = $feature_value->id_feature;
        
        if (!$id_feature) {
            return $updated;
        }
        
        // Query to get total number of products with these attributes
        $count_query = '
            SELECT COUNT(DISTINCT pa.id_product) as total 
            FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
            JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON pa.id_product_attribute = pac.id_product_attribute
            WHERE pac.id_attribute IN (' . implode(',', array_map('intval', $attributes)) . ')';
        
        $total_products = (int)Db::getInstance()->getValue($count_query);
        
        // Process in batches
        $offset = 0;
        while ($offset < $total_products) {
            // Get products with these attributes (batched)
            $sql = 'SELECT DISTINCT pa.id_product 
                    FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
                    JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON pa.id_product_attribute = pac.id_product_attribute
                    WHERE pac.id_attribute IN (' . implode(',', array_map('intval', $attributes)) . ')
                    LIMIT ' . (int)$offset . ', ' . (int)$batch_size;
            
            $products = Db::getInstance()->executeS($sql);
            
            if (!$products) {
                break;
            }
            
            // Associate feature to products in this batch
            foreach ($products as $product) {
                $id_product = (int)$product['id_product'];
                
                // Check if the product already has this feature value
                $exists = Db::getInstance()->getValue('
                    SELECT COUNT(*)
                    FROM ' . _DB_PREFIX_ . 'feature_product
                    WHERE id_product = ' . $id_product . '
                    AND id_feature = ' . $id_feature . '
                    AND id_feature_value = ' . $id_feature_value
                );
                
                if (!$exists) {
                    // Add feature to product
                    Db::getInstance()->insert('feature_product', [
                        'id_feature' => $id_feature,
                        'id_product' => $id_product,
                        'id_feature_value' => $id_feature_value,
                    ]);
                    $updated++;
                }
            }
            
            // Move to the next batch
            $offset += $batch_size;
        }
        
        return $updated;
    }
}