<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminAttributeFeatureConnectorController extends ModuleAdminController
{
    protected $import_preview = null;
    protected $import_payload = '';

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
        
        // Get only attribute-mapped feature values (not category-mapped ones)
        $mappedFeatureValues = AttributeFeatureConnector::getMappedFeatureValues();
        
        foreach ($features as $feature) {
            $feature_values = FeatureValue::getFeatureValuesWithLang(
                $this->context->language->id,
                $feature['id_feature']
            );
            
            foreach ($feature_values as $value) {
                // Skip already mapped feature values if not editing
                if (!Tools::getValue('edit_mapping') && in_array($value['id_feature_value'], $mappedFeatureValues)) {
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
        
        // Pagination for mappings
        $page = (int)Tools::getValue('page', 1);
        $items_per_page = 10;
        
        // Get total count of mappings
        $total_mappings = (int)Db::getInstance()->getValue('
            SELECT COUNT(*) 
            FROM ' . _DB_PREFIX_ . 'attribute_feature_mapping afm
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
        
        $pagination_links = $this->generatePaginationLinks($page, $total_pages);
        
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
            'export_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector') . '&action=exportMappings',
            'cancel_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector'),
            'analytics_url' => $this->context->link->getAdminLink('AdminAttributeFeatureAnalytics'),
            'category_mapping_url' => $this->context->link->getAdminLink('AdminCategoryFeatureMapping'),
            'current_page' => $page,
            'total_pages' => $total_pages,
            'pagination_links' => $pagination_links,
            'items_per_page' => $items_per_page,
            'total_mappings' => $total_mappings,
            'cron_token' => $cron_token,
            'cron_url' => $cron_url,
            'batch_size' => $batch_size,
            'realtime_enabled' => (bool)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_REALTIME'),
            'update_realtime_url' => $this->context->link->getAdminLink('AdminAttributeFeatureConnector'),
            'documentation' => $documentation,
            'import_preview' => $this->import_preview,
            'import_payload' => $this->import_payload,
        ]);
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'attributefeatureconnector/views/templates/admin/configure.tpl');
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
            if ($batch_size > 0) {
                Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', $batch_size);
                $this->confirmations[] = $this->l('Batch size updated successfully');
            } else {
                $this->errors[] = $this->l('Batch size must be greater than 0');
            }
        } elseif (Tools::isSubmit('update_realtime')) {
            $realtime = (int)(bool)Tools::getValue('realtime_enabled');
            Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_REALTIME', $realtime);
            $this->confirmations[] = $realtime
                ? $this->l('Real-time processing enabled')
                : $this->l('Real-time processing disabled');
        } elseif (Tools::isSubmit('preview_import_mappings')) {
            $parsed = $this->parseUploadedImportMappings();
            if (!$parsed['success']) {
                $this->errors[] = $parsed['message'];
            } else {
                $this->import_preview = $this->buildImportPreview($parsed['mappings']);
                $this->import_payload = base64_encode(json_encode($parsed['mappings']));
            }
        } elseif (Tools::isSubmit('confirm_import_mappings')) {
            $payload = base64_decode((string)Tools::getValue('import_payload'), true);
            $parsed = $this->parseImportMappingsContent($payload);
            if (!$parsed['success']) {
                $this->errors[] = $parsed['message'];
                parent::postProcess();
                return;
            }

            $preview = $this->buildImportPreview($parsed['mappings']);
            $result = $this->applyImportPreview($preview);
            if ($result['success']) {
                $this->confirmations[] = sprintf(
                    $this->l('Import finished. %d mappings created, %d mappings updated, %d attributes linked, %d skipped.'),
                    $result['created'],
                    $result['updated'],
                    $result['linked'],
                    $result['skipped']
                );
            } else {
                $this->errors[] = $result['message'];
            }
        } elseif (Tools::getValue('action') === 'exportMappings') {
            $this->exportMappings();
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
    
    protected function getMappings($page = 1, $items_per_page = 10)
    {
        $mappings = [];
        $offset = ($page - 1) * $items_per_page;
        
        $query = new DbQuery();
        $query->select('afm.*, fvl.value, f.name as feature_name, f.id_feature, GROUP_CONCAT(al.name SEPARATOR ", ") as attributes')
            ->from('attribute_feature_mapping', 'afm')
            ->leftJoin('attribute_feature_mapping_attributes', 'afma', 'afm.id_mapping = afma.id_mapping')
            ->leftJoin('feature_value_lang', 'fvl', 'afm.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('feature_value', 'fv', 'fvl.id_feature_value = fv.id_feature_value')
            ->leftJoin('feature_lang', 'f', 'fv.id_feature = f.id_feature AND f.id_lang = ' . (int)$this->context->language->id)
            ->leftJoin('attribute_lang', 'al', 'afma.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id);
        
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

    protected function exportMappings()
    {
        $mappings = Db::getInstance()->executeS(
            'SELECT afm.id_mapping, afm.id_feature_value, fl.name AS feature_name, fvl.value AS feature_value
             FROM `' . _DB_PREFIX_ . 'attribute_feature_mapping` afm
             INNER JOIN `' . _DB_PREFIX_ . 'feature_value` fv ON afm.id_feature_value = fv.id_feature_value
             INNER JOIN `' . _DB_PREFIX_ . 'feature_lang` fl
                ON fv.id_feature = fl.id_feature AND fl.id_lang = ' . (int)$this->context->language->id . '
             INNER JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl
                ON afm.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id . '
             ORDER BY fl.name ASC, fvl.value ASC'
        );

        $payload = [
            'module' => 'attributefeatureconnector',
            'version' => $this->module->version,
            'exported_at' => date('c'),
            'language_id' => (int)$this->context->language->id,
            'mappings' => [],
        ];

        foreach ($mappings ?: [] as $mapping) {
            $attributes = Db::getInstance()->executeS(
                'SELECT agl.name AS group_name, al.name AS attribute_name
                 FROM `' . _DB_PREFIX_ . 'attribute_feature_mapping_attributes` afma
                 INNER JOIN `' . _DB_PREFIX_ . 'attribute` a ON afma.id_attribute = a.id_attribute
                 INNER JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                    ON a.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id . '
                 INNER JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                    ON a.id_attribute_group = agl.id_attribute_group AND agl.id_lang = ' . (int)$this->context->language->id . '
                 WHERE afma.id_mapping = ' . (int)$mapping['id_mapping'] . '
                 ORDER BY agl.name ASC, al.name ASC'
            );

            $payload['mappings'][] = [
                'feature' => $mapping['feature_name'],
                'feature_value' => $mapping['feature_value'],
                'attributes' => array_map(function ($attribute) {
                    return [
                        'group' => $attribute['group_name'],
                        'value' => $attribute['attribute_name'],
                    ];
                }, $attributes ?: []),
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="attribute-feature-mappings-' . date('Y-m-d-His') . '.json"');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function parseUploadedImportMappings()
    {
        if (empty($_FILES['mapping_import_file']) || !isset($_FILES['mapping_import_file']['tmp_name'])) {
            return ['success' => false, 'message' => $this->l('Please choose a JSON file to import.')];
        }

        if (!empty($_FILES['mapping_import_file']['error'])) {
            return ['success' => false, 'message' => $this->l('The uploaded file could not be read.')];
        }

        $content = file_get_contents($_FILES['mapping_import_file']['tmp_name']);
        return $this->parseImportMappingsContent($content);
    }

    protected function parseImportMappingsContent($content)
    {
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return ['success' => false, 'message' => $this->l('Invalid JSON file.')];
        }

        $mappings = isset($data['mappings']) && is_array($data['mappings']) ? $data['mappings'] : $data;
        if (!is_array($mappings)) {
            return ['success' => false, 'message' => $this->l('No mappings were found in the import file.')];
        }

        return ['success' => true, 'mappings' => $mappings];
    }

    protected function buildImportPreview(array $mappings)
    {
        $result = [
            'success' => true,
            'summary' => [
                'create' => 0,
                'update' => 0,
                'link' => 0,
                'duplicate' => 0,
                'skipped' => 0,
            ],
            'rows' => [],
            'mappings' => [],
        ];

        foreach ($mappings as $index => $mapping) {
            $feature_name = trim((string)($mapping['feature'] ?? ''));
            $feature_value = trim((string)($mapping['feature_value'] ?? ''));
            $attributes = isset($mapping['attributes']) && is_array($mapping['attributes']) ? $mapping['attributes'] : [];

            if ($feature_name === '' || $feature_value === '' || !$attributes) {
                $result['summary']['skipped']++;
                $result['rows'][] = $this->buildImportPreviewRow($feature_name, $feature_value, '', '', 'skipped', $this->l('Missing feature, feature value, or attributes.'));
                continue;
            }

            $id_feature_value = $this->findFeatureValueIdByName($feature_name, $feature_value);
            if (!$id_feature_value) {
                $result['summary']['skipped']++;
                $result['rows'][] = $this->buildImportPreviewRow($feature_name, $feature_value, '', '', 'feature_not_found', $this->l('Feature value not found.'));
                continue;
            }

            $id_mapping = (int)Db::getInstance()->getValue(
                'SELECT id_mapping FROM `' . _DB_PREFIX_ . 'attribute_feature_mapping`
                 WHERE id_feature_value = ' . (int)$id_feature_value
            );
            $mapping_action = $id_mapping ? 'update' : 'create';
            $has_linkable_attribute = false;

            $normalized = [
                'id_feature_value' => (int)$id_feature_value,
                'feature' => $feature_name,
                'feature_value' => $feature_value,
                'attributes' => [],
            ];

            foreach ($attributes as $attribute) {
                $group_name = trim((string)($attribute['group'] ?? ''));
                $attribute_name = trim((string)($attribute['value'] ?? ''));
                $id_attribute = $this->findAttributeIdByName($group_name, $attribute_name);

                if (!$id_attribute) {
                    $result['summary']['skipped']++;
                    $result['rows'][] = $this->buildImportPreviewRow($feature_name, $feature_value, $group_name, $attribute_name, 'attribute_not_found', $this->l('Attribute not found.'));
                    continue;
                }

                $is_duplicate = $id_mapping && $this->mappingHasAttribute($id_mapping, $id_attribute);
                if ($is_duplicate) {
                    $result['summary']['duplicate']++;
                    $result['rows'][] = $this->buildImportPreviewRow($feature_name, $feature_value, $group_name, $attribute_name, 'duplicate', $this->l('Already linked.'));
                    continue;
                }

                $has_linkable_attribute = true;
                $result['summary']['link']++;
                $result['rows'][] = $this->buildImportPreviewRow($feature_name, $feature_value, $group_name, $attribute_name, $mapping_action, $mapping_action === 'create' ? $this->l('Will create mapping and link attribute.') : $this->l('Will add attribute to existing mapping.'));
                $normalized['attributes'][] = [
                    'id_attribute' => (int)$id_attribute,
                    'group' => $group_name,
                    'value' => $attribute_name,
                ];
            }

            if ($has_linkable_attribute) {
                $result['summary'][$mapping_action]++;
                $result['mappings'][] = $normalized;
            }
        }

        return $result;
    }

    protected function buildImportPreviewRow($feature_name, $feature_value, $group_name, $attribute_name, $status, $message)
    {
        return [
            'feature' => $feature_name,
            'feature_value' => $feature_value,
            'group' => $group_name,
            'attribute' => $attribute_name,
            'status' => $status,
            'message' => $message,
        ];
    }

    protected function applyImportPreview(array $preview)
    {
        $result = [
            'success' => true,
            'created' => 0,
            'updated' => 0,
            'linked' => 0,
            'skipped' => 0,
        ];

        foreach ($preview['mappings'] as $mapping) {
            $id_mapping = (int)Db::getInstance()->getValue(
                'SELECT id_mapping FROM `' . _DB_PREFIX_ . 'attribute_feature_mapping`
                 WHERE id_feature_value = ' . (int)$mapping['id_feature_value']
            );

            if ($id_mapping) {
                Db::getInstance()->update('attribute_feature_mapping', [
                    'date_upd' => date('Y-m-d H:i:s'),
                ], 'id_mapping = ' . (int)$id_mapping);
                $result['updated']++;
            } else {
                Db::getInstance()->insert('attribute_feature_mapping', [
                    'id_feature_value' => (int)$mapping['id_feature_value'],
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s'),
                ]);
                $id_mapping = (int)Db::getInstance()->Insert_ID();
                $result['created']++;
            }

            foreach ($mapping['attributes'] as $attribute) {
                if ($this->linkAttributeToMapping($id_mapping, (int)$attribute['id_attribute'])) {
                    $result['linked']++;
                }
            }
        }

        $result['skipped'] = (int)$preview['summary']['skipped'] + (int)$preview['summary']['duplicate'];
        return $result;
    }

    protected function findFeatureValueIdByName($feature_name, $feature_value)
    {
        return (int)Db::getInstance()->getValue(
            'SELECT fv.id_feature_value
             FROM `' . _DB_PREFIX_ . 'feature_value` fv
             INNER JOIN `' . _DB_PREFIX_ . 'feature_lang` fl
                ON fv.id_feature = fl.id_feature AND fl.id_lang = ' . (int)$this->context->language->id . '
             INNER JOIN `' . _DB_PREFIX_ . 'feature_value_lang` fvl
                ON fv.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . (int)$this->context->language->id . '
             WHERE fl.name = "' . pSQL($feature_name) . '"
               AND fvl.value = "' . pSQL($feature_value) . '"'
        );
    }

    protected function findAttributeIdByName($group_name, $attribute_name)
    {
        return (int)Db::getInstance()->getValue(
            'SELECT a.id_attribute
             FROM `' . _DB_PREFIX_ . 'attribute` a
             INNER JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
                ON a.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id . '
             INNER JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
                ON a.id_attribute_group = agl.id_attribute_group AND agl.id_lang = ' . (int)$this->context->language->id . '
             WHERE agl.name = "' . pSQL($group_name) . '"
               AND al.name = "' . pSQL($attribute_name) . '"'
        );
    }

    protected function linkAttributeToMapping($id_mapping, $id_attribute)
    {
        if ($this->mappingHasAttribute($id_mapping, $id_attribute)) {
            return false;
        }

        return (bool)Db::getInstance()->insert('attribute_feature_mapping_attributes', [
            'id_mapping' => (int)$id_mapping,
            'id_attribute' => (int)$id_attribute,
        ]);
    }

    protected function mappingHasAttribute($id_mapping, $id_attribute)
    {
        return (bool)Db::getInstance()->getValue(
            'SELECT COUNT(*)
             FROM `' . _DB_PREFIX_ . 'attribute_feature_mapping_attributes`
             WHERE id_mapping = ' . (int)$id_mapping . '
               AND id_attribute = ' . (int)$id_attribute
        );
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

        $id_feature = (int)Db::getInstance()->getValue(
            'SELECT id_feature FROM `' . _DB_PREFIX_ . 'feature_value`
             WHERE id_feature_value = ' . (int)$id_feature_value
        );

        if (!$id_feature) {
            return ['updated' => 0, 'processed' => 0];
        }

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
    
    protected function undoMapping($id_mapping)
    {
        $query = new DbQuery();
        $query->select('afm.id_feature_value')
            ->from('attribute_feature_mapping', 'afm')
            ->where('afm.id_mapping = ' . (int)$id_mapping);

        $result = Db::getInstance()->getRow($query);

        if (!$result) {
            return ['success' => false, 'updated' => 0, 'processed' => 0];
        }

        $id_feature_value = (int)$result['id_feature_value'];
        $id_feature = (int)Db::getInstance()->getValue(
            'SELECT id_feature FROM `' . _DB_PREFIX_ . 'feature_value`
             WHERE id_feature_value = ' . $id_feature_value
        );

        $batch_size = (int)Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        $updated = 0;
        $processed = 0;
        $offset = 0;

        while (true) {
            $products = Db::getInstance()->executeS(
                'SELECT id_product FROM `' . _DB_PREFIX_ . 'feature_product`
                 WHERE id_feature = ' . $id_feature . ' AND id_feature_value = ' . $id_feature_value . '
                 LIMIT ' . (int)$offset . ', ' . (int)$batch_size
            );

            if (!$products) {
                break;
            }

            $product_ids = array_column($products, 'id_product');
            $processed += count($product_ids);
            $updated += AttributeFeatureConnector::removeFeatureFromProducts($id_feature, $id_feature_value, $product_ids);
            $offset += $batch_size;

            if (count($products) < $batch_size) {
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
                'content' => $this->l('This module allows you to automatically assign features to products based on their attributes or categories. This is useful for filtering purposes and improving product search capabilities.'),
                'contact' => $this->l('If you need help please contact developer amurdato@gmail.com')
            ],
            'mappings' => [
                'title' => $this->l('Attribute Mappings'),
                'content' => $this->l('An attribute mapping connects one feature value with one or more product attributes. The picker lets you filter by attribute group, search attributes, add visible results, and review selected attributes before saving.'),
                'steps' => [
                    $this->l('Select a feature value from the dropdown list'),
                    $this->l('Use the attribute group filter or search field to find the attributes you need'),
                    $this->l('Move attributes from Available attributes to Selected attributes'),
                    $this->l('Save the mapping'),
                    $this->l('Use the "Generate Features" button to apply the mapping to existing products')
                ]
            ],
            'importExport' => [
                'title' => $this->l('Import / Export'),
                'content' => $this->l('Use JSON export before reinstalling the module or moving mappings between shops. Import does not rely on old database IDs; it matches by feature name, feature value, attribute group, and attribute value.'),
                'steps' => [
                    $this->l('Click Export JSON to download all current attribute mappings'),
                    $this->l('After reinstalling, upload the JSON file and click Preview Import'),
                    $this->l('Review the import report table for new mappings, updates, duplicates, and missing items'),
                    $this->l('Click Confirm Import only when the preview looks correct')
                ],
                'notes' => [
                    $this->l('Import uses merge mode: existing mappings are kept and only missing attribute links are added'),
                    $this->l('Rows with missing features or attributes are skipped and shown in the report')
                ]
            ],
            'categoryMapping' => [
                'title' => $this->l('Category Mappings'),
                'content' => $this->l('A category mapping connects a feature value with a product category. All products in that category will automatically receive the feature value.'),
                'steps' => [
                    $this->l('Go to the Category-Feature Mapping tab'),
                    $this->l('Select a feature value from the dropdown list'),
                    $this->l('Select a product category from the tree'),
                    $this->l('Save the mapping'),
                    $this->l('Use the "Generate Features" button to apply the mapping to existing products')
                ]
            ],
            'preview' => [
                'title' => $this->l('Preview Function'),
                'content' => $this->l('Before applying a saved mapping to products, use Preview to see which products will be affected. Import also has its own preview report before any mappings are written to the database.'),
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
            'bestPractices' => [
                'title' => $this->l('Best Practices'),
                'tips' => [
                    $this->l('Create clear, specific mappings to avoid confusion'),
                    $this->l('Use preview before applying changes to large product sets'),
                    $this->l('Export mappings before reinstalling or resetting the module'),
                    $this->l('Check the import report before confirming imported mappings'),
                    $this->l('Schedule CRON jobs during off-peak hours'),
                    $this->l('Regularly check and update your mappings as your catalog grows'),
                    $this->l('Consider the impact on product filtering when creating mappings')
                ]
            ],
            'analytics' => [
                'title' => $this->l('Performance Analytics'),
                'content' => $this->l('The Analytics dashboard helps you optimize module performance and identify potential issues with your mappings.'),
                'features' => [
                    $this->l('Performance metrics track execution time and memory usage'),
                    $this->l('Conflict detection finds and helps resolve conflicting attribute mappings'),
                    // Removed auto-attribute suggestions feature reference
                    $this->l('Batch size optimization based on your server performance')
                ]
            ],
            'support' => [
                'title' => $this->l('Support'),
                'content' => $this->l('If you need help please contact developer amurdato@gmail.com')
            ]
        ];
    }
    
}
