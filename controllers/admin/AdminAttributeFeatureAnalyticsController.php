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
        $logs = $this->getPerformanceLogs();
        $metrics = $this->calculatePerformanceMetrics($logs);
        $optimal_batch_size = $this->calculateOptimalBatchSize($logs);
        $current_batch_size = Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        $has_chart_data = !empty($logs);
        $performance_graph_data = $this->preparePerformanceGraphData($logs);
        $unmapped_attributes = $this->getUnmappedAttributes();
        $total_attributes = $this->getTotalAttributesCount();

        $this->context->smarty->assign([
            'performance_logs' => $logs,
            'metrics' => $metrics,
            'has_chart_data' => $has_chart_data,
            'performance_graph_data' => json_encode($performance_graph_data),
            'optimal_batch_size' => $optimal_batch_size,
            'current_batch_size' => $current_batch_size,
            'unmapped_attributes' => $unmapped_attributes,
            'total_attributes' => $total_attributes,
            'unmapped_count' => count($unmapped_attributes),
            'update_batch_url' => $this->context->link->getAdminLink('AdminAttributeFeatureAnalytics') . '&action=updateBatchSize',
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

        $metrics['avg_execution_time'] = round($total_execution_time / $metrics['total_operations'], 2);

        $memory_logs = array_filter($logs, function ($log) { return !empty($log['memory_usage']); });
        $metrics['avg_memory_usage'] = count($memory_logs) > 0
            ? round($total_memory_usage / count($memory_logs))
            : 0;

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
            return 50;
        }

        $batch_metrics = [];
        foreach ($logs as $log) {
            $batch_size = $log['batch_size'];
            if (!isset($batch_metrics[$batch_size])) {
                $batch_metrics[$batch_size] = ['total_time' => 0, 'total_products' => 0, 'count' => 0];
            }
            $batch_metrics[$batch_size]['total_time'] += $log['execution_time'];
            $batch_metrics[$batch_size]['total_products'] += $log['products_processed'];
            $batch_metrics[$batch_size]['count']++;
        }

        $batch_speeds = [];
        foreach ($batch_metrics as $size => $metrics) {
            $avg_time = $metrics['total_time'] / $metrics['count'];
            $avg_products = $metrics['total_products'] / $metrics['count'];
            if ($avg_time > 0) {
                $batch_speeds[$size] = $avg_products / $avg_time;
            }
        }

        if (empty($batch_speeds)) {
            return 50;
        }

        $optimal_size = array_search(max($batch_speeds), $batch_speeds);
        return max(10, min(500, $optimal_size));
    }

    /**
     * Get all attributes that have no entry in attribute_feature_mapping_attributes.
     * Sorted by id_attribute DESC so newest attributes appear first.
     */
    protected function getUnmappedAttributes()
    {
        $result = Db::getInstance()->executeS(
            'SELECT a.id_attribute, al.name AS attribute_name,
                    ag.id_attribute_group, agl.name AS group_name
             FROM `' . _DB_PREFIX_ . 'attribute` a
             LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                   ON a.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id . '
             LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag
                   ON a.id_attribute_group = ag.id_attribute_group
             LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                   ON ag.id_attribute_group = agl.id_attribute_group AND agl.id_lang = ' . (int)$this->context->language->id . '
             WHERE a.id_attribute NOT IN (
                 SELECT id_attribute FROM `' . _DB_PREFIX_ . 'attribute_feature_mapping_attributes`
             )
             ORDER BY a.id_attribute DESC'
        );

        return $result ? $result : [];
    }

    protected function getTotalAttributesCount()
    {
        return (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'attribute`'
        );
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

        parent::postProcess();
    }
}
