<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AttributeFeatureConnectorCronModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
        
        // Disable the header, footer and left/right columns
        $this->display_header = false;
        $this->display_footer = false;
        $this->display_column_left = false;
        $this->display_column_right = false;
    }
    
    public function initContent()
    {
        parent::initContent();
        
        // Check if the secure token is provided and valid
        $provided_token = Tools::getValue('token');
        $configured_token = Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN');
        
        if (empty($provided_token) || $provided_token !== $configured_token) {
            header('HTTP/1.1 403 Forbidden');
            die('Access denied: Invalid token');
        }
        
        // Load the module admin controller to access its methods
        require_once(_PS_MODULE_DIR_ . $this->module->name . '/controllers/admin/AdminAttributeFeatureConnectorController.php');
        
        $controller = new AdminAttributeFeatureConnectorController();
        
        // Generate all features
        $result = $controller->generateAllFeatures();
        
        // Output the result
        header('Content-Type: application/json');
        die(json_encode($result));
    }
}