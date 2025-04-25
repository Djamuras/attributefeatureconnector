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
        
        // Generate all features - implementation moved directly into this controller
        $result = $this->generateAllFeatures();
        
        // Output the result
        header('Content-Type: application/json');
        die(json_encode($result));
    }
    
    /**
     * Generate features for all mappings
     */
    protected function generateAllFeatures()
    {
        $updated = 0;
        
        // Get all mappings
        $mappings = [];
        $query = new DbQuery();
        $query->select('afm.id_feature_value, afma.id_attribute')
            ->from('attribute_feature_mapping', 'afm')
            ->leftJoin('attribute_feature_mapping_attributes', 'afma', 'afm.id_mapping = afma.id_mapping');
        
        $result = Db::getInstance()->executeS($query);
        
        if (!$result) {
            return ['success' => false, 'updated' => 0];
        }
        
        // Reorganize data for easier processing
        foreach ($result as $row) {
            if (!isset($mappings[$row['id_feature_value']])) {
                $mappings[$row['id_feature_value']] = [];
            }
            $mappings[$row['id_feature_value']][] = $row['id_attribute'];
        }
        
        // Process each mapping
        foreach ($mappings as $id_feature_value => $attributes) {
            $updated += $this->processFeatureMapping($id_feature_value, $attributes);
        }
        
        return ['success' => true, 'updated' => $updated];
    }
    
    /**
     * Process a single feature mapping
     */
    protected function processFeatureMapping($id_feature_value, $attributes)
    {
        $updated = 0;
        
        // Get products with these attributes
        $sql = 'SELECT DISTINCT pa.id_product 
                FROM ' . _DB_PREFIX_ . 'product_attribute_combination pac
                JOIN ' . _DB_PREFIX_ . 'product_attribute pa ON pa.id_product_attribute = pac.id_product_attribute
                WHERE pac.id_attribute IN (' . implode(',', array_map('intval', $attributes)) . ')';
        
        $products = Db::getInstance()->executeS($sql);
        
        if (!$products) {
            return $updated;
        }
        
        // Get feature information
        $feature_value = new FeatureValue($id_feature_value);
        $id_feature = $feature_value->id_feature;
        
        if (!$id_feature) {
            return $updated;
        }
        
        // Associate feature to products
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
        
        return $updated;
    }
}