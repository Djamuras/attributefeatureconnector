<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_5_5($module)
{
    Configuration::deleteByName('ATTRIBUTE_FEATURE_CONNECTOR_FRONT_DISPLAY');
    Configuration::deleteByName('ATTRIBUTE_FEATURE_CONNECTOR_FRONT_TITLE');

    return $module->unregisterHook('displayHeader')
        && $module->unregisterHook('displayProductAdditionalInfo');
}
