<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AttributeFeatureConnector extends Module
{
    public function __construct()
    {
        $this->name = 'attributefeatureconnector';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'Dainius';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Attribute-Feature Connector');
        $this->description = $this->l('Automatically assign features to products based on their attributes');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        include(dirname(__FILE__).'/sql/install.php');
        
        // Generate a secure random token for CRON job
        $token = bin2hex(random_bytes(16)); // 32 characters long
        Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN', $token);
        
        // Set default batch size for processing
        Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE', 50);
        
        return parent::install() &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->installTab();
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        
        // Remove configuration values
        Configuration::deleteByName('ATTRIBUTE_FEATURE_CONNECTOR_CRON_TOKEN');
        Configuration::deleteByName('ATTRIBUTE_FEATURE_CONNECTOR_BATCH_SIZE');
        
        return parent::uninstall() &&
            $this->uninstallTab();
    }

    protected function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAttributeFeatureConnector';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Attribute-Feature Connector';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentModulesSf');
        $tab->module = $this->name;
        
        return $tab->add();
    }

    protected function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminAttributeFeatureConnector');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        
        return true;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminAttributeFeatureConnector'));
    }

    public function hookActionAdminControllerSetMedia()
    {
        if (Tools::getValue('controller') === 'AdminAttributeFeatureConnector') {
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        }
    }
}