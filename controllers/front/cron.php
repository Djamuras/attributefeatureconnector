<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AttributeFeatureConnectorCronModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $auth = false;
    /** @var bool */
    public $ajax = true;
    /** @var bool */
    public $ssl = true;

    /**
     * Process the CRON request
     */
    public function postProcess()
    {
        // Disable display of the front controller
        $this->display_header = false;
        $this->display_footer = false;
        
        // Check the security token
        $secure_key = Tools::getValue('secure_key', false);
        if (!$secure_key || $secure_key !== Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_SECURE_KEY')) {
            $this->respondWithError('Invalid security token');
            return;
        }
        
        // Process the action
        $action = Tools::getValue('action', 'generate_all');
        
        switch ($action) {
            case 'generate_all':
                $this->processGenerateAll();
                break;
            default:
                $this->respondWithError('Invalid action');
                break;
        }
    }
    
    /**
     * Generate features for all mappings
     */
    protected function processGenerateAll()
    {
        $result = $this->generateAllFeatures();
        
        if ($result['success']) {
            $this->respondWithSuccess(sprintf('All features generated successfully. %d products updated.', $result['updated']));
        } else {
            $this->respondWithError('Error generating features');
        }
    }
    
    /**
     * Generate all features based on mappings
     *
     * @return array Result with success status and count of updated products
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
        
        // Add log entry
        PrestaShopLogger::addLog(
            'AttributeFeatureConnector CRON: Generated all features. ' . $updated . ' products updated.',
            1,
            null,
            'AttributeFeatureConnector',
            null,
            true
        );
        
        return ['success' => true, 'updated' => $updated];
    }
    
    /**
     * Process a single feature mapping
     *
     * @param int $id_feature_value Feature value ID
     * @param array $attributes Array of attribute IDs
     * @return int Number of products updated
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
    
    /**
     * Send a success response
     *
     * @param string $message Success message
     */
    protected function respondWithSuccess($message)
    {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => $message
        ]);
        exit;
    }
    
    /**
     * Send an error response
     *
     * @param string $message Error message
     */
    protected function respondWithError($message)
    {
        header('HTTP/1.1 400 Bad Request');
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
        exit;
    }
}