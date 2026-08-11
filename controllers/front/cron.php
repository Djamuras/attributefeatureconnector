<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AttributeFeatureConnectorCronModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();

        $this->display_header = false;
        $this->display_footer = false;
        $this->display_column_left = false;
        $this->display_column_right = false;
    }

    public function initContent()
    {
        parent::initContent();

        $provided_token = Tools::getValue('token');
        $configured_token = Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN');

        if (empty($provided_token) || !hash_equals($configured_token, $provided_token)) {
            header('HTTP/1.1 403 Forbidden');
            die('Access denied: Invalid token');
        }

        @set_time_limit(0);

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
                $attribute_result = $this->generateAttributeFeatures();
                $category_result = $this->generateCategoryFeatures();
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

        header('Content-Type: application/json');
        die(json_encode($result));
    }

    protected function generateAttributeFeatures()
    {
        $updated = 0;
        $processed = 0;
        $errors = [];
        $start_time = microtime(true);

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

        $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);

        foreach ($result as $mapping) {
            $id_mapping = $mapping['id_mapping'];
            try {
                $mapping_result = $this->processAttributeMapping($id_mapping, $batch_size);
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

    protected function generateCategoryFeatures()
    {
        $updated = 0;
        $processed = 0;
        $errors = [];
        $start_time = microtime(true);

        $query = new DbQuery();
        $query->select('id_mapping, id_feature_value, id_category, include_subcategories')
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

        $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);

        foreach ($result as $mapping) {
            $id_mapping = $mapping['id_mapping'];
            try {
                $mapping_result = $this->processCategoryMapping(
                    $id_mapping,
                    $mapping['id_feature_value'],
                    $mapping['id_category'],
                    (bool)$mapping['include_subcategories'],
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

    protected function processAttributeMapping($id_mapping, $batch_size = null)
    {
        $start_time = microtime(true);

        $query = new DbQuery();
        $query->select('afm.id_feature_value, fv.id_feature, afma.id_attribute')
            ->from('attribute_feature_mapping', 'afm')
            ->leftJoin('attribute_feature_mapping_attributes', 'afma', 'afm.id_mapping = afma.id_mapping')
            ->leftJoin('feature_value', 'fv', 'afm.id_feature_value = fv.id_feature_value')
            ->where('afm.id_mapping = ' . (int)$id_mapping);

        $result = Db::getInstance()->executeS($query);

        if (!$result || empty($result[0]['id_feature'])) {
            return [
                'success' => false,
                'updated' => 0,
                'processed' => 0,
                'message' => 'Attribute mapping not found or has no attributes',
                'execution_time' => $this->getExecutionTime($start_time)
            ];
        }

        $id_feature_value = (int)$result[0]['id_feature_value'];
        $id_feature = (int)$result[0]['id_feature'];
        $attributes = array_filter(array_column($result, 'id_attribute'));

        if (empty($attributes)) {
            return [
                'success' => false,
                'updated' => 0,
                'processed' => 0,
                'message' => 'No attributes for this mapping',
                'execution_time' => $this->getExecutionTime($start_time)
            ];
        }

        if ($batch_size === null) {
            $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        }

        $counts = $this->runAttributeBatchLoop($id_feature, $id_feature_value, $attributes, $batch_size);

        AttributeFeatureConnector::logPerformance(
            'cron_attribute_mapping',
            $id_mapping,
            $counts['processed'],
            $counts['updated'],
            $this->getExecutionTime($start_time),
            $batch_size
        );

        return [
            'success' => true,
            'updated' => $counts['updated'],
            'processed' => $counts['processed'],
            'execution_time' => $this->getExecutionTime($start_time)
        ];
    }

    protected function processCategoryMapping($id_mapping, $id_feature_value, $id_category, $include_subcategories = false, $batch_size = null)
    {
        $start_time = microtime(true);

        $id_feature = (int)Db::getInstance()->getValue(
            'SELECT id_feature FROM `' . _DB_PREFIX_ . 'feature_value`
             WHERE id_feature_value = ' . (int)$id_feature_value
        );

        if (!$id_feature) {
            return [
                'success' => false,
                'updated' => 0,
                'processed' => 0,
                'message' => 'Feature value not found',
                'execution_time' => $this->getExecutionTime($start_time)
            ];
        }

        if ($batch_size === null) {
            $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        }

        $category_ids = AttributeFeatureConnector::getCategoryIds((int)$id_category, $include_subcategories);
        $counts = $this->runCategoryBatchLoop($id_feature, $id_feature_value, $category_ids, $batch_size);

        AttributeFeatureConnector::logPerformance(
            'cron_category_mapping',
            $id_mapping,
            $counts['processed'],
            $counts['updated'],
            $this->getExecutionTime($start_time),
            $batch_size
        );

        return [
            'success' => true,
            'updated' => $counts['updated'],
            'processed' => $counts['processed'],
            'execution_time' => $this->getExecutionTime($start_time)
        ];
    }

    /**
     * Batch loop for attribute-based product fetching + bulk INSERT IGNORE
     */
    protected function runAttributeBatchLoop($id_feature, $id_feature_value, array $attributes, $batch_size)
    {
        $updated = 0;
        $processed = 0;
        $offset = 0;
        $attr_list = implode(',', array_map('intval', $attributes));

        while (true) {
            $products = Db::getInstance()->executeS(
                'SELECT DISTINCT pa.id_product
                 FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                 JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON pa.id_product_attribute = pac.id_product_attribute
                 WHERE pac.id_attribute IN (' . $attr_list . ')
                 LIMIT ' . (int)$offset . ', ' . (int)$batch_size
            );

            if (!$products) {
                break;
            }

            $product_ids = array_column($products, 'id_product');
            $processed += count($product_ids);
            $updated += AttributeFeatureConnector::assignFeatureToProducts($id_feature, $id_feature_value, $product_ids);
            $offset += $batch_size;

            if (count($products) < $batch_size) {
                break;
            }
        }

        return ['updated' => $updated, 'processed' => $processed];
    }

    /**
     * Batch loop for category-based product fetching + bulk INSERT IGNORE
     */
    protected function runCategoryBatchLoop($id_feature, $id_feature_value, array $category_ids, $batch_size)
    {
        $updated = 0;
        $processed = 0;
        $offset = 0;
        $cat_list = implode(',', array_map('intval', $category_ids));

        while (true) {
            $products = Db::getInstance()->executeS(
                'SELECT DISTINCT p.id_product
                 FROM `' . _DB_PREFIX_ . 'product` p
                 JOIN `' . _DB_PREFIX_ . 'category_product` cp ON p.id_product = cp.id_product
                 WHERE cp.id_category IN (' . $cat_list . ')
                 LIMIT ' . (int)$offset . ', ' . (int)$batch_size
            );

            if (!$products) {
                break;
            }

            $product_ids = array_column($products, 'id_product');
            $processed += count($product_ids);
            $updated += AttributeFeatureConnector::assignFeatureToProducts($id_feature, $id_feature_value, $product_ids);
            $offset += $batch_size;

            if (count($products) < $batch_size) {
                break;
            }
        }

        return ['updated' => $updated, 'processed' => $processed];
    }

    private function getExecutionTime($start_time)
    {
        return round(microtime(true) - $start_time, 2);
    }
}
