<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminAttributeFeatureAnalyticsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        
        parent::__construct();
        
        $this->meta_title = $this->l('Attribute-Feature Analytics');
        
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules'));
        }
    }
    
    public function initContent()
    {
        $this->content .= $this->renderAnalyticsDashboard();
        
        parent::initContent();
    }
    
    public function renderAnalyticsDashboard()
    {
        // Get performance logs
        $logs = $this->getPerformanceLogs();
        
        // Get performance metrics
        $metrics = $this->calculatePerformanceMetrics($logs);
        
        // Get conflict analysis
        $conflicts = $this->detectMappingConflicts();
        
        // Get attribute suggestions
        $suggestions = $this->getAttributeSuggestions();
        
        // Optimal batch size recommendation
        $optimal_batch_size = $this->calculateOptimalBatchSize($logs);
        
        // Get current batch size
        $current_batch_size = Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        
        // Check if we have a chart datapoint
        $has_chart_data = !empty($logs);
        
        // Prepare graph data
        $performance_graph_data = $this->preparePerformanceGraphData($logs);
        
        // Get categories
        $categories = $this->getMappingCategories();
        
        $this->context->smarty->assign([
            'performance_logs' => $logs,
            'metrics' => $metrics,
            'conflicts' => $conflicts,
            'suggestions' => $suggestions,
            'has_chart_data' => $has_chart_data,
            'performance_graph_data' => json_encode($performance_graph_data),
            'categories' => $categories,
            'optimal_batch_size' => $optimal_batch_size,
            'current_batch_size' => $current_batch_size,
            'update_batch_url' => $this->context->link->getAdminLink('AdminAttributeFeatureAnalytics') . '&action=updateBatchSize',
            'process_suggestion_url' => $this->context->link->getAdminLink('AdminAttributeFeatureAnalytics') . '&action=processSuggestion',
            'ignore_suggestion_url' => $this->context->link->getAdminLink('AdminAttributeFeatureAnalytics') . '&action=ignoreSuggestion',
            'resolve_conflict_url' => $this->context->link->getAdminLink('AdminAttributeFeatureAnalytics') . '&action=resolveConflict',
            'analyze_product_url' => $this->context->link->getAdminLink('AdminAttributeFeatureAnalytics') . '&action=analyzeProducts',
            'connector_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector')
        ]);
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'attributefeatureconnector/views/templates/admin/analytics.tpl');
    }
    
    protected function getPerformanceLogs($limit = 100)
    {
        $query = new DbQuery();
        $query->select('*')
            ->from('attribute_feature_performance_log')
            ->orderBy('date_add DESC')
            ->limit((int)$limit);
        
        $result = Db::getInstance()->executeS($query);
        
        return $result ? $result : [];
    }
    
    protected function calculatePerformanceMetrics($logs)
    {
        $metrics = [
            'total_operations' => 0,
            'total_products_processed' => 0,
            'total_products_updated' => 0,
            'avg_execution_time' => 0,
            'max_execution_time' => 0,
            'max_memory_usage' => 0,
            'avg_memory_usage' => 0,
        ];
        
        if (empty($logs)) {
            return $metrics;
        }
        
        $total_execution_time = 0;
        $total_memory_usage = 0;
        
        foreach ($logs as $log) {
            $metrics['total_operations']++;
            $metrics['total_products_processed'] += $log['products_processed'];
            $metrics['total_products_updated'] += $log['products_updated'];
            $total_execution_time += $log['execution_time'];
            
            if ($log['execution_time'] > $metrics['max_execution_time']) {
                $metrics['max_execution_time'] = $log['execution_time'];
            }
            
            if ($log['memory_usage'] && $log['memory_usage'] > $metrics['max_memory_usage']) {
                $metrics['max_memory_usage'] = $log['memory_usage'];
            }
            
            if ($log['memory_usage']) {
                $total_memory_usage += $log['memory_usage'];
            }
        }
        
        $metrics['avg_execution_time'] = $metrics['total_operations'] > 0 ? 
            round($total_execution_time / $metrics['total_operations'], 2) : 0;
            
        $memory_logs = array_filter($logs, function($log) { return !empty($log['memory_usage']); });
        $metrics['avg_memory_usage'] = count($memory_logs) > 0 ? 
            round($total_memory_usage / count($memory_logs)) : 0;
            
        // Convert memory to MB for readability
        $metrics['max_memory_usage'] = round($metrics['max_memory_usage'] / (1024 * 1024), 2);
        $metrics['avg_memory_usage'] = round($metrics['avg_memory_usage'] / (1024 * 1024), 2);
        
        return $metrics;
    }
    
    protected function preparePerformanceGraphData($logs)
    {
        $graph_data = [
            'labels' => [],
            'execution_times' => [],
            'products_processed' => [],
            'batch_sizes' => []
        ];
        
        // Reverse logs to show chronological order
        $logs = array_reverse($logs);
        
        foreach ($logs as $log) {
            $date = new DateTime($log['date_add']);
            $graph_data['labels'][] = $date->format('m/d/Y H:i');
            $graph_data['execution_times'][] = $log['execution_time'];
            $graph_data['products_processed'][] = $log['products_processed'];
            $graph_data['batch_sizes'][] = $log['batch_size'];
        }
        
        return $graph_data;
    }
    
    protected function calculateOptimalBatchSize($logs)
    {
        if (empty($logs)) {
            return 50; // Default recommendation
        }
        
        // Group logs by batch size
        $batch_metrics = [];
        
        foreach ($logs as $log) {
            $batch_size = $log['batch_size'];
            
            if (!isset($batch_metrics[$batch_size])) {
                $batch_metrics[$batch_size] = [
                    'total_time' => 0,
                    'total_products' => 0,
                    'count' => 0
                ];
            }
            
            $batch_metrics[$batch_size]['total_time'] += $log['execution_time'];
            $batch_metrics[$batch_size]['total_products'] += $log['products_processed'];
            $batch_metrics[$batch_size]['count']++;
        }
        
        // Calculate average processing speed (products per second) for each batch size
        $batch_speeds = [];
        
        foreach ($batch_metrics as $size => $metrics) {
            $avg_time = $metrics['total_time'] / $metrics['count'];
            $avg_products = $metrics['total_products'] / $metrics['count'];
            
            if ($avg_time > 0) {
                $speed = $avg_products / $avg_time;
                $batch_speeds[$size] = $speed;
            }
        }
        
        // Find batch size with highest speed
        if (empty($batch_speeds)) {
            return 50; // Default
        }
        
        $optimal_size = array_search(max($batch_speeds), $batch_speeds);
        
        // Ensure we don't recommend too small or large batch sizes
        if ($optimal_size < 10) {
            $optimal_size = 10;
        } elseif ($optimal_size > 500) {
            $optimal_size = 500;
        }
        
        return $optimal_size;
    }
    
    protected function detectMappingConflicts()
    {
        $conflicts = [];
        
        // Get all mappings
        $query = new DbQuery();
        $query->select('m.id_mapping, m.id_feature_value, fv.id_feature, fl.name as feature_name, 
                      fvl.value as feature_value, ma.id_attribute')
            ->from('attribute_feature_mapping', 'm')
            ->leftJoin('attribute_feature_mapping_attributes', 'ma', 'm.id_mapping = ma.id_mapping')
            ->leftJoin('feature_value', 'fv', 'm.id_feature_value = fv.id_feature_value')
            ->leftJoin('feature', 'f', 'fv.id_feature = f.id_feature')
            ->leftJoin('feature_lang', 'fl', 'f.id_feature = fl.id_feature AND fl.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('feature_value_lang', 'fvl', 'fv.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id);
            
        $mappings = Db::getInstance()->executeS($query);
        
        if (!$mappings) {
            return $conflicts;
        }
        
        // Group mappings by attribute to find conflicts
        $attribute_mappings = [];
        
        foreach ($mappings as $mapping) {
            $id_attribute = $mapping['id_attribute'];
            
            if (!$id_attribute) {
                continue;
            }
            
            if (!isset($attribute_mappings[$id_attribute])) {
                $attribute_mappings[$id_attribute] = [];
            }
            
            $attribute_mappings[$id_attribute][] = [
                'id_mapping' => $mapping['id_mapping'],
                'id_feature' => $mapping['id_feature'],
                'id_feature_value' => $mapping['id_feature_value'],
                'feature_name' => $mapping['feature_name'],
                'feature_value' => $mapping['feature_value']
            ];
        }
        
        // Find attributes with multiple feature values for the same feature
        foreach ($attribute_mappings as $id_attribute => $attr_mappings) {
            $features = [];
            
            foreach ($attr_mappings as $mapping) {
                $id_feature = $mapping['id_feature'];
                
                if (!isset($features[$id_feature])) {
                    $features[$id_feature] = [];
                }
                
                $features[$id_feature][] = $mapping;
            }
            
            // Check for conflicts (multiple values for the same feature)
            foreach ($features as $id_feature => $feature_mappings) {
                if (count($feature_mappings) > 1) {
                    // Get attribute name
                    $attribute_name = Db::getInstance()->getValue('
                        SELECT al.name 
                        FROM ' . _DB_PREFIX_ . 'attribute a
                        JOIN ' . _DB_PREFIX_ . 'attribute_lang al ON a.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id . '
                        WHERE a.id_attribute = ' . (int)$id_attribute
                    );
                    
                    $conflicts[] = [
                        'id_attribute' => $id_attribute,
                        'attribute_name' => $attribute_name,
                        'feature_name' => $feature_mappings[0]['feature_name'],
                        'id_feature' => $id_feature,
                        'mappings' => $feature_mappings
                    ];
                }
            }
        }
        
        return $conflicts;
    }
    
    protected function getAttributeSuggestions($limit = 10)
    {
        $query = new DbQuery();
        $query->select('s.*, pl.name as product_name')
            ->from('attribute_feature_suggestion', 's')
            ->leftJoin('product_lang', 'pl', 's.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id)
            ->where('s.processed = 0')
            ->orderBy('s.confidence DESC')
            ->limit((int)$limit);
            
        $result = Db::getInstance()->executeS($query);
        
        return $result ? $result : [];
    }
    
    protected function getMappingCategories()
    {
        $query = new DbQuery();
        $query->select('*')
            ->from('attribute_feature_mapping_category')
            ->orderBy('name ASC');
            
        $result = Db::getInstance()->executeS($query);
        
        return $result ? $result : [];
    }
    
    public function postProcess()
    {
        if (Tools::getValue('action') === 'updateBatchSize') {
            $batch_size = (int)Tools::getValue('batch_size');
            
            if ($batch_size >= 10 && $batch_size <= 500) {
                Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', $batch_size);
                $this->confirmations[] = $this->l('Batch size updated successfully.');
            } else {
                $this->errors[] = $this->l('Batch size must be between 10 and 500.');
            }
        }
        elseif (Tools::getValue('action') === 'processSuggestion') {
            $id_suggestion = (int)Tools::getValue('id_suggestion');
            
            if ($id_suggestion) {
                // Mark as processed
                Db::getInstance()->update('attribute_feature_suggestion', 
                    ['processed' => 1], 
                    'id_suggestion = ' . (int)$id_suggestion
                );
                
                $this->confirmations[] = $this->l('Suggestion processed successfully.');
            }
        }
        elseif (Tools::getValue('action') === 'ignoreSuggestion') {
            $id_suggestion = (int)Tools::getValue('id_suggestion');
            
            if ($id_suggestion) {
                // Mark as processed (ignored)
                Db::getInstance()->update('attribute_feature_suggestion', 
                    ['processed' => 1], 
                    'id_suggestion = ' . (int)$id_suggestion
                );
                
                $this->confirmations[] = $this->l('Suggestion ignored.');
            }
        }
        elseif (Tools::getValue('action') === 'resolveConflict') {
            $id_attribute = (int)Tools::getValue('id_attribute');
            $id_feature = (int)Tools::getValue('id_feature');
            $keep_mapping_id = (int)Tools::getValue('keep_mapping_id');
            
            if ($id_attribute && $id_feature && $keep_mapping_id) {
                // Get all conflicting mapping IDs
                $query = new DbQuery();
                $query->select('DISTINCT m.id_mapping')
                    ->from('attribute_feature_mapping', 'm')
                    ->leftJoin('attribute_feature_mapping_attributes', 'ma', 'm.id_mapping = ma.id_mapping')
                    ->leftJoin('feature_value', 'fv', 'm.id_feature_value = fv.id_feature_value')
                    ->where('ma.id_attribute = ' . (int)$id_attribute)
                    ->where('fv.id_feature = ' . (int)$id_feature)
                    ->where('m.id_mapping != ' . (int)$keep_mapping_id);
                    
                $conflicting_mappings = Db::getInstance()->executeS($query);
                
                if ($conflicting_mappings) {
                    foreach ($conflicting_mappings as $mapping) {
                        // Remove attribute from conflicting mappings
                        Db::getInstance()->delete('attribute_feature_mapping_attributes',
                            'id_mapping = ' . (int)$mapping['id_mapping'] . ' AND id_attribute = ' . (int)$id_attribute
                        );
                    }
                    
                    $this->confirmations[] = $this->l('Conflict resolved successfully.');
                }
            }
        }
        elseif (Tools::getValue('action') === 'analyzeProducts') {
            $result = $this->analyzeProductDescriptions();
            
            if ($result['success']) {
                $this->confirmations[] = sprintf($this->l('Product analysis completed. %d potential attributes found.'), $result['suggestions']);
            } else {
                $this->errors[] = $this->l('Error analyzing products.');
            }
        }
        
        parent::postProcess();
    }
    
    protected function analyzeProductDescriptions()
    {
        $suggestions_count = 0;
        
        // Get products that haven't been analyzed yet
        $query = new DbQuery();
        $query->select('p.id_product, pl.description, pl.description_short')
            ->from('product', 'p')
            ->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('attribute_feature_suggestion', 's', 'p.id_product = s.id_product')
            ->where('s.id_suggestion IS NULL')
            ->limit(100);
            
        $products = Db::getInstance()->executeS($query);
        
        if (!$products) {
            return ['success' => false, 'suggestions' => 0];
        }
        
        // Get existing attribute names for comparison
        $query = new DbQuery();
        $query->select('DISTINCT al.name')
            ->from('attribute_lang', 'al')
            ->where('al.id_lang = ' . (int)$this->context->language->id);
            
        $existing_attributes = Db::getInstance()->executeS($query);
        $existing_names = [];
        
        if ($existing_attributes) {
            foreach ($existing_attributes as $attr) {
                $existing_names[] = strtolower($attr['name']);
            }
        }
        
        foreach ($products as $product) {
            // Combine description and short description
            $text = $product['description'] . ' ' . $product['description_short'];
            
            // Strip HTML tags
            $text = strip_tags($text);
            
            // Simple attribute extraction using common patterns
            $potential_attributes = $this->extractPotentialAttributes($text, $existing_names);
            
            // Save suggestions
            foreach ($potential_attributes as $attribute) {
                Db::getInstance()->insert('attribute_feature_suggestion', [
                    'id_product' => (int)$product['id_product'],
                    'text' => pSQL($attribute['text']),
                    'confidence' => (float)$attribute['confidence'],
                    'processed' => 0,
                    'date_add' => date('Y-m-d H:i:s')
                ]);
                
                $suggestions_count++;
            }
        }
        
        return ['success' => true, 'suggestions' => $suggestions_count];
    }
    
    protected function extractPotentialAttributes($text, $existing_names)
    {
        $potential_attributes = [];
        
        // Convert to lowercase for case-insensitive matching
        $text = strtolower($text);
        
        // Look for potential attributes in text
        foreach ($existing_names as $name) {
            if (strpos($text, $name) !== false) {
                // Found existing attribute mentioned in description
                $potential_attributes[] = [
                    'text' => $name,
                    'confidence' => 0.9 // High confidence since it matches existing attribute
                ];
            }
        }
        
        // Common patterns that might indicate attributes
        $patterns = [
            'made of ([a-zA-Z0-9 ]+)' => 0.7,
            'material: ([a-zA-Z0-9 ]+)' => 0.8,
            'color: ([a-zA-Z0-9 ]+)' => 0.8,
            'size: ([a-zA-Z0-9 ]+)' => 0.8,
            'dimensions: ([a-zA-Z0-9 ]+)' => 0.7,
            'features ([a-zA-Z0-9 ]+)' => 0.6,
            'type: ([a-zA-Z0-9 ]+)' => 0.7,
            'style: ([a-zA-Z0-9 ]+)' => 0.7,
            'with ([a-zA-Z0-9 ]+) technology' => 0.6,
            'comes in ([a-zA-Z0-9 ]+)' => 0.5,
            'available in ([a-zA-Z0-9 ]+)' => 0.7
        ];
        
        foreach ($patterns as $pattern => $confidence) {
            if (preg_match_all('/' . $pattern . '/i', $text, $matches)) {
                foreach ($matches[1] as $match) {
                    // Clean up the match
                    $match = trim($match);
                    
                    // Skip if too short or too long
                    if (strlen($match) < 3 || strlen($match) > 30) {
                        continue;
                    }
                    
                    // Skip if already in existing attributes
                    if (in_array(strtolower($match), $existing_names)) {
                        continue;
                    }
                    
                    // Add to potential attributes
                    $potential_attributes[] = [
                        'text' => $match,
                        'confidence' => $confidence
                    ];
                }
            }
        }
        
        return $potential_attributes;
    }
}