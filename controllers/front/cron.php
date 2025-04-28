<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AttributeFeatureConnectorCronModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
        
        // Disable the header, footer and left/right columns
        $this->display_header = false;
        $this->display_footer = false;
        $this->display_column_left = false;
        $this->display_column_right = false;
    }
    
    public function initContent()
    {
        parent::initContent();
        
        // Check if the secure token is provided and valid
        $provided_token = Tools::getValue('token');
        $configured_token = Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN');
        
        if (empty($provided_token) || $provided_token !== $configured_token) {
            header('HTTP/1.1 403 Forbidden');
            die('Access denied: Invalid token');
        }
        
        // Set execution time limit to prevent timeouts on large catalogs
        @set_time_limit(0);
        
        // Get requested operation type
        $operation = Tools::getValue('operation', 'all');
        
        switch ($operation) {
            case 'attribute':
                $result = $this->generateAttributeFeatures();
                break;
            case 'category':
                $result = $this->generateCategoryFeatures();
                break;
            case 'all':
            default:
                // Process both attribute and category mappings
                $attribute_result = $this->generateAttributeFeatures();
                $category_result = $this->generateCategoryFeatures();
                
                // Combine the results
                $result = [
                    'success' => $attribute_result['success'] && $category_result['success'],
                    'updated' => $attribute_result['updated'] + $category_result['updated'],
                    'processed' => $attribute_result['processed'] + $category_result['processed'],
                    'attribute_mappings_processed' => $attribute_result['mappings_processed'],
                    'category_mappings_processed' => $category_result['mappings_processed'],
                    'execution_time' => $attribute_result['execution_time'] + $category_result['execution_time'],
                    'attribute_errors' => $attribute_result['errors'],
                    'category_errors' => $category_result['errors']
                ];
                break;
        }
        
        // Output the result
        header('Content-Type: application/json');
        die(json_encode($result));
    }
    
    /**
     * Generate features for all attribute mappings
     */
    protected function generateAttributeFeatures()
    {
        $updated = 0;
        $processed = 0;
        $errors = [];
        $start_time = microtime(true);
        
        // Get all mappings
        $mappings = [];
        $query = new DbQuery();
        $query->select('afm.id_mapping, afm.id_feature_value')
            ->from('attribute_feature_mapping', 'afm');
        
        $result = Db::getInstance()->executeS($query);
        
        if (!$result) {
            return [
                'success' => false, 
                'updated' => 0,
                'processed' => 0,
                'mappings_processed' => 0,
                'message' => 'No attribute mappings found',
                'execution_time' => $this->getExecutionTime($start_time),
                'errors' => ['No attribute mappings found']
            ];
        }
        
        // Get batch size from configuration
        $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        
        // Process each mapping in batches
        foreach ($result as $mapping) {
            $id_mapping = $mapping['id_mapping'];
            try {
                $mapping_result = $this->generateAttributeFeaturesForMapping($id_mapping, $batch_size);
                if ($mapping_result['success']) {
                    $updated += $mapping_result['updated'];
                    $processed += $mapping_result['processed'];
                } else {
                    $errors[] = 'Error processing attribute mapping ID ' . $id_mapping . ': ' . $mapping_result['message'];
                }
            } catch (Exception $e) {
                $errors[] = 'Exception processing attribute mapping ID ' . $id_mapping . ': ' . $e->getMessage();
            }
        }
        
        return [
            'success' => true,
            'updated' => $updated,
            'processed' => $processed,
            'mappings_processed' => count($result),
            'errors' => $errors,
            'execution_time' => $this->getExecutionTime($start_time)
        ];
    }
    
    /**
     * Generate features for all category mappings
     */
    protected function generateCategoryFeatures()
    {
        $updated = 0;
        $processed = 0;
        $errors = [];
        $start_time = microtime(true);
        
        // Get all mappings
        $mappings = [];
        $query = new DbQuery();
        $query->select('id_mapping, id_feature_value, id_category')
            ->from('category_feature_mapping');
        
        $result = Db::getInstance()->executeS($query);
        
        if (!$result) {
            return [
                'success' => false, 
                'updated' => 0,
                'processed' => 0,
                'mappings_processed' => 0,
                'message' => 'No category mappings found',
                'execution_time' => $this->getExecutionTime($start_time),
                'errors' => ['No category mappings found']
            ];
        }
        
        // Get batch size from configuration
        $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        
        // Process each mapping in batches
        foreach ($result as $mapping) {
            $id_mapping = $mapping['id_mapping'];
            try {
                $mapping_result = $this->generateCategoryFeaturesForMapping(
                    $id_mapping, 
                    $mapping['id_feature_value'], 
                    $mapping['id_category'], 
                    $batch_size
                );
                
                if ($mapping_result['success']) {
                    $updated += $mapping_result['updated'];
                    $processed += $mapping_result['processed'];
                } else {
                    $errors[] = 'Error processing category mapping ID ' . $id_mapping . ': ' . $mapping_result['message'];
                }
            } catch (Exception $e) {
                $errors[] = 'Exception processing category mapping ID ' . $id_mapping . ': ' . $e->getMessage();
            }
        }
        
        return [
            'success' => true,
            'updated' => $updated,
            'processed' => $processed,
            'mappings_processed' => count($result),
            'errors' => $errors,
            'execution_time' => $this->getExecutionTime($start_time)
        ];
    }
    
    /**
     * Generate features for a specific attribute mapping
     */
    protected function generateAttributeFeaturesForMapping($id_mapping, $batch_size = null)
    {
        $updated = 0;
        $processed = 0;
        $start_time = microtime(true);
        
        // Get mapping details
        $query = new DbQuery();
        $query->select('afm.id_feature_value, afma.id_attribute')
            ->from('attribute_feature_mapping', 'afm')
            ->leftJoin('attribute_feature_mapping_attributes', 'afma', 'afm.id_mapping = afma.id_mapping')
            ->where('afm.id_mapping = ' . (int)$id_mapping);
        
        $result = Db::getInstance()->executeS($query);
        
        if (!$result) {
            return [
                'success' => false, 
                'updated' => 0,
                'processed' => 0,
                'message' => 'Attribute mapping not found or has no attributes',
                'execution_time' => $this->getExecutionTime($start_time)
            ];
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
        $result = $this->processAttributeMappingInBatches($id_feature_value, $attributes, $batch_size);
        $updated = $result['updated'];
        $processed = $result['processed'];
        
        // Log individual mapping performance
        AttributeFeatureConnector::logPerformance(
            'cron_attribute_mapping', 
            $id_mapping,
            $processed,
            $updated,
            $this->getExecutionTime($start_time),
            $batch_size
        );
        
        return [
            'success' => true, 
            'updated' => $updated,
            'processed' => $processed,
            'execution_time' => $this->getExecutionTime($start_time)
        ];
    }
    
    /**
     * Generate features for a specific category mapping
     */
    protected function generateCategoryFeaturesForMapping($id_mapping, $id_feature_value, $id_category, $batch_size = null)
    {
        $updated = 0;
        $processed = 0;
        $start_time = microtime(true);
        
        // If batch_size is not provided, get it from configuration
        if ($batch_size === null) {
            $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        }
        
        // Process the mapping with batch processing
        $result = $this->processCategoryMappingInBatches($id_feature_value, $id_category, $batch_size);
        $updated = $result['updated'];
        $processed = $result['processed'];
        
        // Log individual mapping performance
        AttributeFeatureConnector::logPerformance(
            'cron_category_mapping', 
            $id_mapping,
            $processed,
            $updated,
            $this->getExecutionTime($start_time),
            $batch_size
        );
        
        return [
            'success' => true, 
            'updated' => $updated,
            'processed' => $processed,
            'execution_time' => $this->getExecutionTime($start_time)
        ];
    }
    
    /**
     * Process an attribute mapping in batches
     */
    protected function processAttributeMappingInBatches($id_feature_value, $attributes, $batch_size)
    {
        $updated = 0;
        $processed = 0;
        $offset = 0;
        
        // Get feature information
        $feature_value = new FeatureValue($id_feature_value);
        $id_feature = $feature_value->id_feature;
        
        if (!$id_feature) {
            return ['updated' => 0, 'processed' => 0];
        }
        
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
        
        if (!$id_feature) {
            return ['updated' => 0, 'processed' => 0];
        }
        
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
     * Calculate execution time in seconds
     */
    private function getExecutionTime($start_time)
    {
        return round(microtime(true) - $start_time, 2);
    }
}