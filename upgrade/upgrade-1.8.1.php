<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_8_1($module)
{
    AttributeFeatureConnector::ensureRuntimeSchema();
    AttributeFeatureConnector::initializeAttributeNotificationBaseline(true);

    return $module->registerHook('actionObjectAttributeAddAfter');
}
