<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminCategoryFeatureMappingController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        
        parent::__construct();
        
        $this->meta_title = $this->l('Category-Feature Mapping');
        
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
        // Get all features, excluding already mapped ones
        $features = Feature::getFeatures($this->context->language->id);
        $feature_options = [];
        
        // Get both attribute-mapped and category-mapped feature values
        $mappedFeatureValues = array_merge(
            AttributeFeatureConnector::getMappedFeatureValues(),
            AttributeFeatureConnector::getCategoryMappedFeatureValues()
        );
        
        foreach ($features as $feature) {
            $feature_values = FeatureValue::getFeatureValuesWithLang(
                $this->context->language->id,
                $feature['id_feature']
            );
            
            foreach ($feature_values as $value) {
                // Skip already mapped feature values
                if (in_array($value['id_feature_value'], $mappedFeatureValues)) {
                    continue;
                }
                
                $feature_options[] = [
                    'id' => $value['id_feature_value'],
                    'name' => $feature['name'] . ' - ' . $value['value'],
                    'feature_id' => $feature['id_feature'],
                    'feature_name' => $feature['name'],
                    'value' => $value['value']
                ];
            }
        }
        
        // Get all categories
        $categories = Category::getCategories($this->context->language->id, true, false);
        $category_options = $this->getCategoryTree($categories);
        
        // Pagination for mappings
        $page = (int)Tools::getValue('page', 1);
        $items_per_page = 10;
        
        // Get total count of mappings
        $total_mappings = (int)Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM ' . _DB_PREFIX_ . 'category_feature_mapping
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
        
        if ($edit_mapping_id) {
            foreach ($mappings as $mapping) {
                if ((int)$mapping['id_mapping'] === $edit_mapping_id) {
                    $mapping_to_edit = $mapping;
                    break;
                }
            }
            
            // If the mapping is not in the current page, fetch it separately
            if (!$mapping_to_edit) {
                $query = new DbQuery();
                $query->select('cfm.*, fvl.value, f.name as feature_name, c.name as category_name')
                    ->from('category_feature_mapping', 'cfm')
                    ->leftJoin('feature_value_lang', 'fvl', 'cfm.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id)
                    ->leftJoin('feature_value', 'fv', 'fvl.id_feature_value = fv.id_feature_value')
                    ->leftJoin('feature_lang', 'f', 'fv.id_feature = f.id_feature AND f.id_lang = ' . (int)$this->context->language->id)
                    ->leftJoin('category_lang', 'c', 'cfm.id_category = c.id_category AND c.id_lang = ' . (int)$this->context->language->id)
                    ->where('cfm.id_mapping = ' . (int)$edit_mapping_id);
                
                $result = Db::getInstance()->executeS($query);
                if ($result && count($result) > 0) {
                    $mapping_to_edit = $result[0];
                }
            }
        }
        
        $pagination_links = $this->generatePaginationLinks($page, $total_pages);
        
        // Get batch processing configuration
        $batch_size = Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        
        $this->context->smarty->assign([
            'feature_options' => $feature_options,
            'category_options' => $category_options,
            'mappings' => $mappings,
            'mapping_to_edit' => $mapping_to_edit,
            'generate_url' => $this->context->link->getAdminLink('AdminCategoryFeatureMapping') . '&action=generateAllFeatures',
            'generate_mapping_url' => $this->context->link->getAdminLink('AdminCategoryFeatureMapping') . '&action=generateFeatures&id_mapping=',
            'undo_mapping_url' => $this->context->link->getAdminLink('AdminCategoryFeatureMapping') . '&action=undoMapping&id_mapping=',
            'preview_url' => $this->context->link->getAdminLink('AdminCategoryFeatureMapping') . '&action=previewMapping&id_mapping=',
            'delete_url' => $this->context->link->getAdminLink('AdminCategoryFeatureMapping') . '&action=deleteMapping',
            'edit_url' => $this->context->link->getAdminLink('AdminCategoryFeatureMapping') . '&action=editMapping',
            'cancel_url' => $this->context->link->getAdminLink('AdminCategoryFeatureMapping'),
            'attribute_connector_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector'),
            'analytics_url' => $this->context->link->getAdminLink('AdminAttributeFeatureAnalytics'),
            'current_page' => $page,
            'total_pages' => $total_pages,
            'pagination_links' => $pagination_links,
            'items_per_page' => $items_per_page,
            'total_mappings' => $total_mappings,
            'batch_size' => $batch_size
        ]);
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'attributefeatureconnector/views/templates/admin/category_mapping.tpl');
    }
    
    /**
     * Get all mappings with pagination
     */
    protected function getMappings($page = 1, $items_per_page = 10)
    {
        $mappings = [];
        $offset = ($page - 1) * $items_per_page;
        
        $query = new DbQuery();
        $query->select('cfm.*, fvl.value, fv.id_feature, f.name as feature_name, c.name as category_name')
            ->from('category_feature_mapping', 'cfm')
            ->leftJoin('feature_value_lang', 'fvl', 'cfm.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('feature_value', 'fv', 'fvl.id_feature_value = fv.id_feature_value')
            ->leftJoin('feature_lang', 'f', 'fv.id_feature = f.id_feature AND f.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('category_lang', 'c', 'cfm.id_category = c.id_category AND c.id_lang = ' . (int)$this->context->language->id)
            ->orderBy('cfm.date_add DESC')
            ->limit($items_per_page, $offset);
        
        $result = Db::getInstance()->executeS($query);
        if ($result) {
            $mappings = $result;
        }
        
        return $mappings;
    }
    
    /**
     * Get category tree for dropdown
     */
    protected function getCategoryTree($categories, $prefix = '')
    {
        $tree = [];
        
        foreach ($categories as $category) {
            // Skip root and home category
            if ($category['id_category'] <= 1) {
                continue;
            }
            
            $tree[] = [
                'id' => $category['id_category'],
                'name' => $prefix . $category['name']
            ];
            
            if (isset($category['children']) && is_array($category['children']) && !empty($category['children'])) {
                $childTree = $this->getCategoryTree($category['children'], $prefix . '&nbsp;&nbsp;');
                $tree = array_merge($tree, $childTree);
            }
        }
        
        return $tree;
    }
    
    /**
     * Generate pagination links
     */
    private function generatePaginationLinks($current_page, $total_pages)
    {
        $links = [];
        $base_url = $this->context->link->getAdminLink('AdminCategoryFeatureMapping') . '&page=';
        
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
        if (Tools::isSubmit('submitCategoryMapping')) {
            $id_feature_value = (int)Tools::getValue('id_feature_value');
            $id_category = (int)Tools::getValue('id_category');
            
            if (!$id_feature_value || !$id_category) {
                $this->errors[] = $this->l('Please select both a feature value and a category');
                return;
            }
            
            // Check if mapping already exists
            $existing = Db::getInstance()->getValue('
                SELECT COUNT(*) 
                FROM ' . _DB_PREFIX_ . 'category_feature_mapping 
                WHERE id_feature_value = ' . $id_feature_value . ' 
                AND id_category = ' . $id_category
            );
            
            if ($existing) {
                $this->errors[] = $this->l('This category-feature mapping already exists');
                return;
            }
            
            $this->saveMapping($id_feature_value, $id_category);
            $this->confirmations[] = $this->l('Category-Feature mapping saved successfully');
        } elseif (Tools::isSubmit('submitEditMapping')) {
            $id_mapping = (int)Tools::getValue('id_mapping');
            $id_category = (int)Tools::getValue('id_category');
            
            if (!$id_mapping || !$id_category) {
                $this->errors[] = $this->l('Invalid mapping information');
                return;
            }
            
            // Get current feature value 
            $id_feature_value = Db::getInstance()->getValue('
                SELECT id_feature_value 
                FROM ' . _DB_PREFIX_ . 'category_feature_mapping 
                WHERE id_mapping = ' . $id_mapping
            );
            
            // Check if the new mapping would create a duplicate
            $existing = Db::getInstance()->getValue('
                SELECT COUNT(*) 
                FROM ' . _DB_PREFIX_ . 'category_feature_mapping 
                WHERE id_feature_value = ' . $id_feature_value . ' 
                AND id_category = ' . $id_category . '
                AND id_mapping != ' . $id_mapping
            );
            
            if ($existing) {
                $this->errors[] = $this->l('This category-feature mapping already exists');
                return;
            }
            
            $this->updateMapping($id_mapping, $id_category);
            $this->confirmations[] = $this->l('Category-Feature mapping updated successfully');
        } elseif (Tools::getValue('action') === 'deleteMapping') {
            $id_mapping = (int)Tools::getValue('id_mapping');
            if ($id_mapping) {
                $this->deleteMapping($id_mapping);
                $this->confirmations[] = $this->l('Mapping deleted successfully');
            }
        } elseif (Tools::getValue('action') === 'generateAllFeatures') {
            $start_time = microtime(true);
            $result = $this->generateAllFeatures();
            $execution_time = microtime(true) - $start_time;
            
            // Log performance
            AttributeFeatureConnector::logPerformance(
                'category_generate_all', 
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
                    'category_generate_single', 
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
                    'category_undo_mapping', 
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
        } elseif (Tools::getValue('action') === 'previewMapping') {
            $id_mapping = (int)Tools::getValue('id_mapping');
            if ($id_mapping) {
                $preview_results = $this->previewMapping($id_mapping);
                if ($preview_results) {
                    $this->context->smarty->assign([
                        'preview_results' => $preview_results,
                        'mapping_id' => $id_mapping,
                        'back_url' => $this->context->link->getAdminLink('AdminCategoryFeatureMapping')
                    ]);
                    
                    $this->content = $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'attributefeatureconnector/views/templates/admin/category_preview.tpl');
                    return;
                } else {
                    $this->errors[] = $this->l('No products found for this mapping preview');
                }
            }
        }
        
        parent::postProcess();
    }
    
    /**
     * Save a new category-feature mapping
     */
    protected function saveMapping($id_feature_value, $id_category)
    {
        // Insert mapping
        $mapping = [
            'id_feature_value' => (int)$id_feature_value,
            'id_category' => (int)$id_category,
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        
        return Db::getInstance()->insert('category_feature_mapping', $mapping);
    }
    
    /**
     * Update an existing category-feature mapping
     */
    protected function updateMapping($id_mapping, $id_category)
    {
        // Update mapping
        return Db::getInstance()->update('category_feature_mapping', [
            'id_category' => (int)$id_category,
            'date_upd' => date('Y-m-d H:i:s'),
        ], 'id_mapping = ' . (int)$id_mapping);
    }
    
    /**
     * Delete a category-feature mapping
     */
    protected function deleteMapping($id_mapping)
    {
        return Db::getInstance()->delete('category_feature_mapping', 'id_mapping = ' . (int)$id_mapping);
    }
    
    /**
     * Generate features for all category-feature mappings
     */
    public function generateAllFeatures()
    {
        $updated = 0;
        $processed = 0;
        
        // Get all mappings
        $mappings = [];
        $query = new DbQuery();
        $query->select('id_mapping')
            ->from('category_feature_mapping');
        
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
    
    /**
     * Generate features for a specific category-feature mapping
     */
    protected function generateFeaturesForMapping($id_mapping, $batch_size = null)
    {
        $updated = 0;
        $processed = 0;
        
        // Get mapping details
        $query = new DbQuery();
        $query->select('id_feature_value, id_category')
            ->from('category_feature_mapping')
            ->where('id_mapping = ' . (int)$id_mapping);
        
        $mapping = Db::getInstance()->getRow($query);
        
        if (!$mapping) {
            return ['success' => false, 'updated' => 0, 'processed' => 0];
        }
        
        $id_feature_value = $mapping['id_feature_value'];
        $id_category = $mapping['id_category'];
        
        // If batch_size is not provided, get it from configuration
        if ($batch_size === null) {
            $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        }
        
        // Process the mapping with batch processing
        $result = $this->processCategoryMappingInBatches($id_feature_value, $id_category, $batch_size);
        $updated = $result['updated'];
        $processed = $result['processed'];
        
        return ['success' => true, 'updated' => $updated, 'processed' => $processed];
    }
    
    /**
     * Process a category mapping in batches
     */
    protected function processCategoryMappingInBatches($id_feature_value, $id_category, $batch_size)
    {
        $updated = 0;
        $processed = 0;
        $offset = 0;
        
        // Get feature information
        $feature_value = new FeatureValue($id_feature_value);
        $id_feature = $feature_value->id_feature;
        
        while (true) {
            // Get a batch of products from this category
            $sql = 'SELECT DISTINCT p.id_product 
                    FROM ' . _DB_PREFIX_ . 'product p
                    JOIN ' . _DB_PREFIX_ . 'category_product cp ON p.id_product = cp.id_product
                    WHERE cp.id_category = ' . (int)$id_category . '
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
    
    /**
     * Remove features for a specific mapping
     */
    protected function undoMapping($id_mapping)
    {
        $updated = 0;
        $processed = 0;
        
        // Get mapping details
        $query = new DbQuery();
        $query->select('id_feature_value, id_category')
            ->from('category_feature_mapping')
            ->where('id_mapping = ' . (int)$id_mapping);
        
        $mapping = Db::getInstance()->getRow($query);
        
        if (!$mapping) {
            return ['success' => false, 'updated' => 0, 'processed' => 0];
        }
        
        $id_feature_value = $mapping['id_feature_value'];
        $id_category = $mapping['id_category'];
        
        // Get feature information
        $feature_value = new FeatureValue($id_feature_value);
        $id_feature = $feature_value->id_feature;
        
        // Get batch size from configuration
        $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        $offset = 0;
        
        // Get products from this category
        while (true) {
            $products = Db::getInstance()->executeS('
                SELECT DISTINCT p.id_product
                FROM ' . _DB_PREFIX_ . 'product p
                JOIN ' . _DB_PREFIX_ . 'category_product cp ON p.id_product = cp.id_product
                JOIN ' . _DB_PREFIX_ . 'feature_product fp ON p.id_product = fp.id_product
                WHERE cp.id_category = ' . (int)$id_category . '
                AND fp.id_feature = ' . (int)$id_feature . '
                AND fp.id_feature_value = ' . (int)$id_feature_value . '
                LIMIT ' . (int)$offset . ', ' . (int)$batch_size
            );
            
            if (!$products || empty($products)) {
                break; // No more products to process
            }
            
            $processed += count($products);
            
            // Remove features from products in this batch
            foreach ($products as $product) {
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
            if (count($products) < $batch_size) {
                break;
            }
        }
        
        return ['success' => true, 'updated' => $updated, 'processed' => $processed];
    }
    
    /**
     * Preview products affected by a mapping
     */
    protected function previewMapping($id_mapping, $limit = 10)
    {
        // Get mapping details
        $query = new DbQuery();
        $query->select('cfm.id_feature_value, fvl.value, f.name as feature_name, f.id_feature, c.name as category_name, cfm.id_category')
            ->from('category_feature_mapping', 'cfm')
            ->leftJoin('feature_value_lang', 'fvl', 'cfm.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('feature_value', 'fv', 'fvl.id_feature_value = fv.id_feature_value')
            ->leftJoin('feature_lang', 'f', 'fv.id_feature = f.id_feature AND f.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('category_lang', 'c', 'cfm.id_category = c.id_category AND c.id_lang = ' . (int)$this->context->language->id)
            ->where('cfm.id_mapping = ' . (int)$id_mapping);
        
        $mapping_info = Db::getInstance()->getRow($query);
        
        if (!$mapping_info) {
            return false;
        }
        
        // Get products from the category
        $sql = 'SELECT p.id_product, pl.name as product_name 
                FROM ' . _DB_PREFIX_ . 'product p
                JOIN ' . _DB_PREFIX_ . 'product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . '
                JOIN ' . _DB_PREFIX_ . 'category_product cp ON p.id_product = cp.id_product
                WHERE cp.id_category = ' . (int)$mapping_info['id_category'] . '
                ORDER BY pl.name ASC
                LIMIT ' . (int)$limit;
        
        $affected_products = Db::getInstance()->executeS($sql);
        
        if (!$affected_products) {
            return false;
        }
        
        // Get total count of affected products
        $total_sql = 'SELECT COUNT(DISTINCT p.id_product) 
                FROM ' . _DB_PREFIX_ . 'product p
                JOIN ' . _DB_PREFIX_ . 'category_product cp ON p.id_product = cp.id_product
                WHERE cp.id_category = ' . (int)$mapping_info['id_category'];
        
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
            'category_name' => $mapping_info['category_name'],
            'affected_products' => $affected_products,
            'total_affected' => $total_affected,
            'showing_limit' => $limit
        ];
    }
}