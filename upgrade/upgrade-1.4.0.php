<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_4_0($module)
{
    $db = Db::getInstance();

    // Add include_subcategories column to category_feature_mapping
    $result = $db->execute(
        'ALTER TABLE `' . _DB_PREFIX_ . 'category_feature_mapping`
         ADD COLUMN IF NOT EXISTS `include_subcategories` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
         AFTER `id_category`'
    );

    if (!$result) {
        return false;
    }

    // Add real-time processing config
    Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_REALTIME', 0);

    // Register new real-time hooks
    return $module->registerHook('actionObjectProductAddAfter')
        && $module->registerHook('actionObjectProductUpdateAfter')
        && $module->registerHook('actionProductAttributeUpdate')
        && $module->registerHook('actionCategoryProductAdd')
        && $module->registerHook('actionCategoryProductDelete');
}
