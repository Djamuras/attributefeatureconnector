<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_8_0($module)
{
    Configuration::updateValue(
        'ATTRIBUTE_FEATURE_CONNECTOR_ALERT_EMAIL',
        Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_ALERT_EMAIL') ?: Configuration::get('PS_SHOP_EMAIL')
    );

    AttributeFeatureConnector::ensureRuntimeSchema();
    AttributeFeatureConnector::initializeAttributeNotificationBaseline(true);

    return $module->registerHook('actionObjectAttributeAddAfter');
}
