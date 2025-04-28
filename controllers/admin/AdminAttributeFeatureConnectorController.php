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
        // Get all features
        $features = Feature::getFeatures($this->context->language->id);
        $feature_options = [];
        
        foreach ($features as $feature) {
            $feature_values = FeatureValue::getFeatureValuesWithLang(
                $this->context->language->id,
                $feature['id_feature']
            );
            
            foreach ($feature_values as $value) {
                $feature_options[] = [
                    'id' => $value['id_feature_value'],
                    'name' => $feature['name'] . ' - ' . $value['value'],
                    'feature_id' => $feature['id_feature'],
                    'feature_name' => $feature['name'],
                    'value' => $value['value']
                ];
            }
        }
        
        // Get all attribute groups and attributes
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
                $attribute_options[] = [
                    'id' => $attribute['id_attribute'],
                    'name' => $group['name'] . ' - ' . $attribute['name'],
                    'group_name' => $group['name'],
                    'attribute_name' => $attribute['name']
                ];
            }
        }
        
        // Get all mapping categories
        $categories = $this->getMappingCategories();
        
        // Get selected category filter
        $selected_category = (int)Tools::getValue('category_filter', 0);
        
        // Pagination for mappings
        $page = (int)Tools::getValue('page', 1);
        $items_per_page = 10;
        
        // Build the where clause for category filter
        $category_where = '';
        if ($selected_category > 0) {
            $category_where = ' WHERE afm.id_category = ' . (int)$selected_category;
        }
        
        // Get total count of mappings with filter
        $total_mappings = (int)Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM ' . _DB_PREFIX_ . 'attribute_feature_mapping afm
            ' . $category_where
        );
        
        $total_pages = ceil($total_mappings / $items_per_page);
        if ($page < 1) {
            $page = 1;
        } elseif ($page > $total_pages && $total_pages > 0) {
            $page = $total_pages;
        }
        
        // Get existing mappings with pagination and category filter
        $mappings = $this->getMappings($page, $items_per_page, $selected_category);
        
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
                $query->select('afm.*, fvl.value, f.name as feature_name, GROUP_CONCAT(al.name SEPARATOR ", ") as attributes, c.name as category_name')
                    ->from('attribute_feature_mapping', 'afm')
                    ->leftJoin('attribute_feature_mapping_attributes', 'afma', 'afm.id_mapping = afma.id_mapping')
                    ->leftJoin('feature_value_lang', 'fvl', 'afm.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id)
                    ->leftJoin('feature_value', 'fv', 'fvl.id_feature_value = fv.id_feature_value')
                    ->leftJoin('feature_lang', 'f', 'fv.id_feature = f.id_feature AND f.id_lang = ' . (int)$this->context->language->id)
                    ->leftJoin('attribute_lang', 'al', 'afma.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id)
                    ->leftJoin('attribute_feature_mapping_category', 'c', 'afm.id_category = c.id_category')
                    ->where('afm.id_mapping = ' . (int)$edit_mapping_id)
                    ->groupBy('afm.id_mapping');
                
                $result = Db::getInstance()->executeS($query);
                if ($result && count($result) > 0) {
                    $mapping_to_edit = $result[0];
                    $selected_attributes = $this->getAttributesForMapping($edit_mapping_id);
                }
            }
        }
        
        $pagination_links = $this->generatePaginationLinks($page, $total_pages, $selected_category);
        
        // Get CRON token and URL
        $cron_token = Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN');
        $shop_domain = Context::getContext()->shop->getBaseURL(true);
        $cron_url = $shop_domain . 'index.php?fc=module&module=attributefeatureconnector&controller=cron&token=' . $cron_token;
        
        // Get batch processing configuration
        $batch_size = Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        
        // Documentation content
        $documentation = $this->getDocumentationContent();
        
        $this->context->smarty->assign([
            'feature_options' => $feature_options,
            'attribute_options' => $attribute_options,
            'mappings' => $mappings,
            'mapping_to_edit' => $mapping_to_edit,
            'selected_attributes' => $selected_attributes,
            'generate_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=generateAllFeatures',
            'generate_mapping_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=generateFeatures&id_mapping=',
            'undo_mapping_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=undoMapping&id_mapping=',
            'preview_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=previewMapping&id_mapping=',
            'delete_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=deleteMapping',
            'edit_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=editMapping',
            'cancel_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector'),
            'analytics_url' => $this->context->link->getAdminLink('AdminAttributeFeatureAnalytics'),
            'current_page' => $page,
            'total_pages' => $total_pages,
            'pagination_links' => $pagination_links,
            'items_per_page' => $items_per_page,
            'total_mappings' => $total_mappings,
            'cron_token' => $cron_token,
            'cron_url' => $cron_url,
            'batch_size' => $batch_size,
            'documentation' => $documentation,
            'categories' => $categories,
            'selected_category' => $selected_category,
            'manage_categories_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=manageCategories'
        ]);
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'attributefeatureconnector/views/templates/admin/configure.tpl');
    }
    
    private function generatePaginationLinks($current_page, $total_pages, $category_filter = 0)
    {
        $links = [];
        $base_url = $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&page=';
        
        // Add category filter to URLs if needed
        if ($category_filter > 0) {
            $base_url .= '&category_filter=' . (int)$category_filter;
        }
        
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
            $id_category = (int)Tools::getValue('id_category', 0);
            
            if (!$id_feature_value || !is_array($selected_attributes) || empty($selected_attributes)) {
                $this->errors[] = $this->l('Please select a feature and at least one attribute');
                return;
            }
            
            // If no category is selected, use the default
            if ($id_category <= 0) {
                $id_category = $this->getDefaultCategoryId();
            }
            
            $this->saveMapping($id_feature_value, $selected_attributes, $id_category);
            $this->confirmations[] = $this->l('Mapping saved successfully');
        } elseif (Tools::isSubmit('submitEditMapping')) {
            $id_mapping = (int)Tools::getValue('id_mapping');
            $selected_attributes = Tools::getValue('selected_attributes');
            $id_category = (int)Tools::getValue('id_category', 0);
            
            if (!$id_mapping || !is_array($selected_attributes) || empty($selected_attributes)) {
                $this->errors[] = $this->l('Please select at least one attribute');
                return;
            }
            
            // If no category is selected, keep the existing one
            if ($id_category <= 0) {
                $current_mapping = Db::getInstance()->getRow('
                    SELECT id_category FROM ' . _DB_PREFIX_ . 'attribute_feature_mapping 
                    WHERE id_mapping = ' . (int)$id_mapping
                );
                
                if ($current_mapping && isset($current_mapping['id_category'])) {
                    $id_category = (int)$current_mapping['id_category'];
                } else {
                    $id_category = $this->getDefaultCategoryId();
                }
            }
            
            $this->updateMapping($id_mapping, $selected_attributes, $id_category);
            $this->confirmations[] = $this->l('Mapping updated successfully');
        } elseif (Tools::isSubmit('regenerate_cron_token')) {
            $new_token = bin2hex(random_bytes(16)); // 32 characters long
            Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN', $new_token);
            $this->confirmations[] = $this->l('CRON token regenerated successfully');
        } elseif (Tools::isSubmit('update_batch_size')) {
            $batch_size = (int)Tools::getValue('batch_size');
            if ($batch_size > 0) {
                Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', $batch_size);
                $this->confirmations[] = $this->l('Batch size updated successfully');
            } else {
                $this->errors[] = $this->l('Batch size must be greater than 0');
            }
        } elseif (Tools::isSubmit('submitNewCategory')) {
            $name = Tools::getValue('category_name');
            $description = Tools::getValue('category_description');
            
            if (empty($name)) {
                $this->errors[] = $this->l('Category name is required');
                return;
            }
            
            $result = $this->addMappingCategory($name, $description);
            if ($result) {
                $this->confirmations[] = $this->l('Category added successfully');
            } else {
                $this->errors[] = $this->l('Error adding category');
            }
        } elseif (Tools::isSubmit('submitEditCategory')) {
            $id_category = (int)Tools::getValue('id_category');
            $name = Tools::getValue('category_name');
            $description = Tools::getValue('category_description');
            
            if (!$id_category || empty($name)) {
                $this->errors[] = $this->l('Category ID and name are required');
                return;
            }
            
            $result = $this->updateMappingCategory($id_category, $name, $description);
            if ($result) {
                $this->confirmations[] = $this->l('Category updated successfully');
            } else {
                $this->errors[] = $this->l('Error updating category');
            }
        } elseif (Tools::getValue('action') === 'deleteCategory') {
            $id_category = (int)Tools::getValue('id_category');
            
            if ($id_category) {
                // Check if category is in use
                $in_use = Db::getInstance()->getValue('
                    SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'attribute_feature_mapping 
                    WHERE id_category = ' . (int)$id_category
                );
                
                if ($in_use > 0) {
                    // Move mappings to default category before deleting
                    $default_category_id = $this->getDefaultCategoryId();
                    
                    if ($default_category_id && $default_category_id != $id_category) {
                        Db::getInstance()->update('attribute_feature_mapping',
                            ['id_category' => $default_category_id],
                            'id_category = ' . (int)$id_category
                        );
                    } else {
                        $this->errors[] = $this->l('Cannot delete category: it is being used and no default category exists');
                        return;
                    }
                }
                
                // Delete the category
                $result = Db::getInstance()->delete('attribute_feature_mapping_category', 'id_category = ' . (int)$id_category);
                
                if ($result) {
                    $this->confirmations[] = $this->l('Category deleted successfully');
                } else {
                    $this->errors[] = $this->l('Error deleting category');
                }
            }
        } elseif (Tools::getValue('action') === 'manageCategories') {
            // Display category management interface
            $categories = $this->getMappingCategories();
            $category_to_edit = null;
            
            if (Tools::getValue('edit_category')) {
                $id_category = (int)Tools::getValue('edit_category');
                foreach ($categories as $category) {
                    if ((int)$category['id_category'] === $id_category) {
                        $category_to_edit = $category;
                        break;
                    }
                }
            }
            
            $this->context->smarty->assign([
                'categories' => $categories,
                'category_to_edit' => $category_to_edit,
                'back_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector'),
                'delete_category_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=deleteCategory',
                'edit_category_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=manageCategories&edit_category='
            ]);
            
            $this->content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'attributefeatureconnector/views/templates/admin/categories.tpl');
            return;
        } elseif (Tools::getValue('action') === 'generateAllFeatures') {
            $start_time = microtime(true);
            $result = $this->generateAllFeatures();
            $execution_time = microtime(true) - $start_time;
            
            // Log performance
            AttributeFeatureConnector::logPerformance(
                'generate_all', 
                null, 
                $result['processed'], 
                $result['updated'],
                $execution_time
            );
            
            if ($result['success']) {
                $this->confirmations[] = sprintf($this->l('All features generated successfully. %d products updated.'), $result['updated']);
            } else {
                $this->errors[] = $this->l('Error generating features');
            }
        } elseif (Tools::getValue('action') === 'generateFeatures') {
            $id_mapping = (int)Tools::getValue('id_mapping');
            if ($id_mapping) {
                $start_time = microtime(true);
                $result = $this->generateFeaturesForMapping($id_mapping);
                $execution_time = microtime(true) - $start_time;
                
                // Log performance
                AttributeFeatureConnector::logPerformance(
                    'generate_single', 
                    $id_mapping, 
                    $result['processed'], 
                    $result['updated'],
                    $execution_time
                );
                
                if ($result['success']) {
                    $this->confirmations[] = sprintf($this->l('Features for this mapping generated successfully. %d products updated.'), $result['updated']);
                } else {
                    $this->errors[] = $this->l('Error generating features for this mapping');
                }
            }
        } elseif (Tools::getValue('action') === 'undoMapping') {
            $id_mapping = (int)Tools::getValue('id_mapping');
            if ($id_mapping) {
                $start_time = microtime(true);
                $result = $this->undoMapping($id_mapping);
                $execution_time = microtime(true) - $start_time;
                
                // Log performance
                AttributeFeatureConnector::logPerformance(
                    'undo_mapping', 
                    $id_mapping, 
                    $result['processed'], 
                    $result['updated'],
                    $execution_time
                );
                
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
        } elseif (Tools::getValue('action') === 'previewMapping') {
            $id_mapping = (int)Tools::getValue('id_mapping');
            if ($id_mapping) {
                $preview_results = $this->previewMapping($id_mapping);
                if ($preview_results) {
                    $this->context->smarty->assign([
                        'preview_results' => $preview_results,
                        'mapping_id' => $id_mapping,
                        'back_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector')
                    ]);
                    
                    $this->content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'attributefeatureconnector/views/templates/admin/preview.tpl');
                    return;
                } else {
                    $this->errors[] = $this->l('No products found for this mapping preview');
                }
            }
        }
        
        parent::postProcess();
    }
    
    protected function getMappings($page = 1, $items_per_page = 10, $category_filter = 0)
    {
        $mappings = [];
        $offset = ($page - 1) * $items_per_page;
        
        $query = new DbQuery();
        $query->select('afm.*, fvl.value, f.name as feature_name, f.id_feature, c.name as category_name, GROUP_CONCAT(al.name SEPARATOR ", ") as attributes')
            ->from('attribute_feature_mapping', 'afm')
            ->leftJoin('attribute_feature_mapping_attributes', 'afma', 'afm.id_mapping = afma.id_mapping')
            ->leftJoin('feature_value_lang', 'fvl', 'afm.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('feature_value', 'fv', 'fvl.id_feature_value = fv.id_feature_value')
            ->leftJoin('feature_lang', 'f', 'fv.id_feature = f.id_feature AND f.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('attribute_lang', 'al', 'afma.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('attribute_feature_mapping_category', 'c', 'afm.id_category = c.id_category');
            
        // Add category filter if needed
        if ($category_filter > 0) {
            $query->where('afm.id_category = ' . (int)$category_filter);
        }
        
        $query->groupBy('afm.id_mapping')
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
    
    protected function saveMapping($id_feature_value, $selected_attributes, $id_category)
    {
        // Insert mapping
        $mapping = [
            'id_feature_value' => (int)$id_feature_value,
            'id_category' => (int)$id_category,
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

    protected function updateMapping($id_mapping, $selected_attributes, $id_category)
    {
        // Update mapping date and category
        Db::getInstance()->update('attribute_feature_mapping', [
            'id_category' => (int)$id_category,
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
        $processed = 0;
        
        // Get all mappings
        $mappings = [];
        $query = new DbQuery();
        $query->select('afm.id_mapping, afm.id_feature_value')
            ->from('attribute_feature_mapping', 'afm');
        
        $result = Db::getInstance()->executeS($query);
        
        if (!$result) {
            return ['success' => false, 'updated' => 0, 'processed' => 0];
        }
        
        // Get batch size from configuration
        $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        
        // Process each mapping in batches
        foreach ($result as $mapping) {
            $id_mapping = $mapping['id_mapping'];
            $mapping_result = $this->generateFeaturesForMapping($id_mapping, $batch_size);
            if ($mapping_result['success']) {
                $updated += $mapping_result['updated'];
                $processed += $mapping_result['processed'];
            }
        }
        
        return ['success' => true, 'updated' => $updated, 'processed' => $processed];
    }
    
    protected function generateFeaturesForMapping($id_mapping, $batch_size = null)
    {
        $updated = 0;
        $processed = 0;
        
        // Get mapping details
        $query = new DbQuery();
        $query->select('afm.id_feature_value, afma.id_attribute')
            ->from('attribute_feature_mapping', 'afm')
            ->leftJoin('attribute_feature_mapping_attributes', 'afma', 'afm.id_mapping = afma.id_mapping')
            ->where('afm.id_mapping = ' . (int)$id_mapping);
        
        $result = Db::getInstance()->executeS($query);
        
        if (!$result) {
            return ['success' => false, 'updated' => 0, 'processed' => 0];
        }
        
        // Organize the attribute IDs
        $id_feature_value = $result[0]['id_feature_value'];
        $attributes = [];
        
        foreach ($result as $row) {
            $attributes[] = $row['id_attribute'];
        }
        
        // If batch_size is not provided, get it from configuration
        if ($batch_size === null) {
            $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        }
        
        // Process the mapping with batch processing
        $result = $this->processMappingInBatches($id_feature_value, $attributes, $batch_size);
        $updated = $result['updated'];
        $processed = $result['processed'];
        
        return ['success' => true, 'updated' => $updated, 'processed' => $processed];
    }
    
    protected function processMappingInBatches($id_feature_value, $attributes, $batch_size)
    {
        $updated = 0;
        $processed = 0;
        $offset = 0;
        
        // Get feature information
        $feature_value = new FeatureValue($id_feature_value);
        $id_feature = $feature_value->id_feature;
        
        while (true) {
            // Get a batch of products with these attributes
            $sql = 'SELECT DISTINCT pa.id_product 
                    FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
                    JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON pa.id_product_attribute = pac.id_product_attribute
                    WHERE pac.id_attribute IN (' . implode(',', array_map('intval', $attributes)) . ')
                    LIMIT ' . (int)$offset . ', ' . (int)$batch_size;
            
            $products = Db::getInstance()->executeS($sql);
            
            if (!$products || empty($products)) {
                break; // No more products to process
            }
            
            $processed += count($products);
            
            // Process this batch
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
            
            // Security: avoid infinite loops
            if (count($products) < $batch_size) {
                break;
            }
        }
        
        return ['updated' => $updated, 'processed' => $processed];
    }
    
    protected function undoMapping($id_mapping)
    {
        $updated = 0;
        $processed = 0;
        
        // Get mapping details
        $query = new DbQuery();
        $query->select('afm.id_feature_value')
            ->from('attribute_feature_mapping', 'afm')
            ->where('afm.id_mapping = ' . (int)$id_mapping);
        
        $result = Db::getInstance()->getRow($query);
        
        if (!$result) {
            return ['success' => false, 'updated' => 0, 'processed' => 0];
        }
        
        $id_feature_value = $result['id_feature_value'];
        
        // Get feature information
        $feature_value = new FeatureValue($id_feature_value);
        $id_feature = $feature_value->id_feature;
        
        // Get batch size from configuration
        $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        $offset = 0;
        
        // Process in batches
        while (true) {
            // Get a batch of products with this feature
            $products_with_feature = Db::getInstance()->executeS('
                SELECT id_product
                FROM ' . _DB_PREFIX_ . 'feature_product
                WHERE id_feature = ' . (int)$id_feature . '
                AND id_feature_value = ' . (int)$id_feature_value . '
                LIMIT ' . (int)$offset . ', ' . (int)$batch_size
            );
            
            if (!$products_with_feature || empty($products_with_feature)) {
                break; // No more products to process
            }
            
            $processed += count($products_with_feature);
            
            // Remove features from products in this batch
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
            
            // Move to the next batch
            $offset += $batch_size;
            
            // Security: avoid infinite loops
            if (count($products_with_feature) < $batch_size) {
                break;
            }
        }
        
        return ['success' => true, 'updated' => $updated, 'processed' => $processed];
    }
    
    protected function previewMapping($id_mapping, $limit = 10)
    {
        // Get mapping details
        $query = new DbQuery();
        $query->select('afm.id_feature_value, fvl.value, f.name as feature_name, f.id_feature')
            ->from('attribute_feature_mapping', 'afm')
            ->leftJoin('feature_value_lang', 'fvl', 'afm.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('feature_value', 'fv', 'fvl.id_feature_value = fv.id_feature_value')
            ->leftJoin('feature_lang', 'f', 'fv.id_feature = f.id_feature AND f.id_lang = ' . (int)$this->context->language->id)
            ->where('afm.id_mapping = ' . (int)$id_mapping);
        
        $mapping_info = Db::getInstance()->getRow($query);
        
        if (!$mapping_info) {
            return false;
        }
        
        // Get attributes for this mapping
        $attributes = $this->getAttributesForMapping($id_mapping);
        
        if (empty($attributes)) {
            return false;
        }
        
        // Get products that would be affected
        $sql = 'SELECT DISTINCT p.id_product, pl.name as product_name, GROUP_CONCAT(DISTINCT al.name SEPARATOR ", ") as matching_attributes 
                FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
                JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON pa.id_product_attribute = pac.id_product_attribute
                JOIN ' . _DB_PREFIX_ . 'product p ON p.id_product = pa.id_product
                JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . '
                LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON pac.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id . '
                WHERE pac.id_attribute IN (' . implode(',', array_map('intval', $attributes)) . ')
                GROUP BY p.id_product
                ORDER BY pl.name ASC
                LIMIT ' . (int)$limit;
        
        $affected_products = Db::getInstance()->executeS($sql);
        
        if (!$affected_products) {
            return false;
        }
        
        // Get total count of affected products
        $total_sql = 'SELECT COUNT(DISTINCT pa.id_product) 
                FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
                JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON pa.id_product_attribute = pac.id_product_attribute
                WHERE pac.id_attribute IN (' . implode(',', array_map('intval', $attributes)) . ')';
        
        $total_affected = Db::getInstance()->getValue($total_sql);
        
        // Check which products already have this feature value
        foreach ($affected_products as &$product) {
            $has_feature = Db::getInstance()->getValue('
                SELECT COUNT(*)
                FROM ' . _DB_PREFIX_ . 'feature_product
                WHERE id_product = ' . (int)$product['id_product'] . '
                AND id_feature = ' . (int)$mapping_info['id_feature'] . '
                AND id_feature_value = ' . (int)$mapping_info['id_feature_value']
            );
            
            $product['already_has_feature'] = (bool)$has_feature;
            
            // Get product link
            $product['edit_url'] = $this->context->link->getAdminLink('AdminProducts', true, [
                'id_product' => $product['id_product'],
                'updateproduct' => 1
            ]);
        }
        
        return [
            'feature_name' => $mapping_info['feature_name'],
            'feature_value' => $mapping_info['value'],
            'affected_products' => $affected_products,
            'total_affected' => $total_affected,
            'showing_limit' => $limit
        ];
    }
    
    protected function getDocumentationContent()
    {
        return [
            'general' => [
                'title' => $this->l('General Information'),
                'content' => $this->l('This module allows you to automatically assign features to products based on their attributes. This is useful for filtering purposes and improving product search capabilities.'),
                'contact' => $this->l('If you need help please contact developer amurdato@gmail.com')
            ],
            'mappings' => [
                'title' => $this->l('Creating Mappings'),
                'content' => $this->l('A mapping connects a feature value with one or more attributes. When a product has any of these attributes, the feature value will be automatically assigned to the product.'),
                'steps' => [
                    $this->l('Select a feature value from the dropdown list'),
                    $this->l('Select a category for better organization (optional)'),
                    $this->l('Select one or more attributes that should trigger this feature value'),
                    $this->l('Save the mapping'),
                    $this->l('Use the "Generate Features" button to apply the mapping to existing products')
                ]
            ],
            'preview' => [
                'title' => $this->l('Preview Function'),
                'content' => $this->l('Before applying a mapping to your products, use the Preview function to see which products will be affected. This helps prevent unintended changes to your catalog.'),
            ],
            'batch' => [
                'title' => $this->l('Batch Processing'),
                'content' => $this->l('For large catalogs, the module uses batch processing to prevent timeout issues. You can adjust the batch size in the settings according to your server capabilities.'),
                'tips' => [
                    $this->l('Smaller batch sizes (20-50) are safer for shared hosting environments'),
                    $this->l('Larger batch sizes (100-200) may be more efficient on dedicated servers'),
                    $this->l('If you experience timeout errors, reduce the batch size')
                ]
            ],
            'cron' => [
                'title' => $this->l('CRON Job Configuration'),
                'content' => $this->l('For regular updates, set up a CRON job to automatically generate features for all products on a scheduled basis. This ensures new products get features assigned properly.'),
            ],
            'categories' => [
                'title' => $this->l('Mapping Categories'),
                'content' => $this->l('Organize your mappings into categories for better management. This is especially useful for shops with many different product types or large numbers of mappings.'),
                'tips' => [
                    $this->l('Create categories based on product types or departments'),
                    $this->l('Use the category filter to quickly find related mappings'),
                    $this->l('Keep category names simple and descriptive')
                ]
            ],
            'bestPractices' => [
                'title' => $this->l('Best Practices'),
                'tips' => [
                    $this->l('Create clear, specific mappings to avoid confusion'),
                    $this->l('Use preview before applying changes to large product sets'),
                    $this->l('Schedule CRON jobs during off-peak hours'),
                    $this->l('Regularly check and update your mappings as your catalog grows'),
                    $this->l('Consider the impact on product filtering when creating mappings'),
                    $this->l('Organize mappings into logical categories')
                ]
            ],
            'analytics' => [
                'title' => $this->l('Performance Analytics'),
                'content' => $this->l('The Analytics dashboard helps you optimize module performance and identify potential issues with your mappings.'),
                'features' => [
                    $this->l('Performance metrics track execution time and memory usage'),
                    $this->l('Conflict detection finds and helps resolve conflicting attribute mappings'),
                    $this->l('Auto-attribute suggestions can identify potential attributes from product descriptions'),
                    $this->l('Batch size optimization based on your server performance')
                ]
            ],
            'support' => [
                'title' => $this->l('Support'),
                'content' => $this->l('If you need help please contact developer amurdato@gmail.com')
            ]
        ];
    }
    
    protected function getMappingCategories()
    {
        $query = new DbQuery();
        $query->select('c.*, (SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'attribute_feature_mapping m WHERE m.id_category = c.id_category) as mappings_count')
            ->from('attribute_feature_mapping_category', 'c')
            ->orderBy('c.name ASC');
            
        $result = Db::getInstance()->executeS($query);
        
        return $result ? $result : [];
    }
    
    protected function getDefaultCategoryId()
    {
        $query = new DbQuery();
        $query->select('id_category')
            ->from('attribute_feature_mapping_category')
            ->where('name = "Default"')
            ->orderBy('id_category ASC')
            ->limit(1);
            
        $result = Db::getInstance()->getValue($query);
        
        if (!$result) {
            // If no default category exists, create one
            $this->addMappingCategory('Default', 'Default category for mappings');
            return $this->getDefaultCategoryId();
        }
        
        return (int)$result;
    }
    
    protected function addMappingCategory($name, $description = '')
    {
        return Db::getInstance()->insert('attribute_feature_mapping_category', [
            'name' => pSQL($name),
            'description' => pSQL($description),
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s')
        ]);
    }
    
    protected function updateMappingCategory($id_category, $name, $description = '')
    {
        return Db::getInstance()->update('attribute_feature_mapping_category', [
            'name' => pSQL($name),
            'description' => pSQL($description),
            'date_upd' => date('Y-m-d H:i:s')
        ], 'id_category = ' . (int)$id_category);
    }
}