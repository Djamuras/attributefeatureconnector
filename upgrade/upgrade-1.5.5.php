<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_5_5($module)
{
    if (Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_FRONT_DISPLAY') === false) {
        Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_FRONT_DISPLAY', 1);
    }

    if (!Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_FRONT_TITLE')) {
        Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_FRONT_TITLE', 'Product highlights');
    }

    return $module->registerHook('displayHeader')
        && $module->registerHook('displayProductAdditionalInfo');
}
