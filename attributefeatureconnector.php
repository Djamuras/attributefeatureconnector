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
        $this->version = '1.0.10'; // Updated version
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
        
        // Generate a secure key for CRON access
        $secure_key = Tools::substr(Tools::encrypt('attribute_feature_connector' . date('YmdHis') . _COOKIE_KEY_), 0, 32);
        Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_SECURE_KEY', $secure_key);
        
        return parent::install() &&
            $this->registerHook('actionAdminControllerSetMedia') &&
            $this->installTab();
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        
        Configuration::deleteByName('ATTRIBUTE_FEATURE_CONNECTOR_SECURE_KEY');
        
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
    
    /**
     * Get CRON URL with security token
     * 
     * @return string CRON URL
     */
    public function getCronUrl()
    {
        $secure_key = Configuration::get('ATTRIBUTE_FEATURE_CONNECTOR_SECURE_KEY');
        if (!$secure_key) {
            // Generate new secure key if not exists
            $secure_key = Tools::substr(Tools::encrypt('attribute_feature_connector' . date('YmdHis') . _COOKIE_KEY_), 0, 32);
            Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_SECURE_KEY', $secure_key);
        }
        
        return Context::getContext()->link->getModuleLink(
            $this->name,
            'cron',
            ['secure_key' => $secure_key, 'action' => 'generate_all']
        );
    }
    
    /**
     * Regenerate security token
     * 
     * @return string New security token
     */
    public function regenerateCronSecureKey()
    {
        $secure_key = Tools::substr(Tools::encrypt('attribute_feature_connector' . date('YmdHis') . _COOKIE_KEY_), 0, 32);
        Configuration::updateValue('ATTRIBUTE_FEATURE_CONNECTOR_SECURE_KEY', $secure_key);
        
        return $secure_key;
    }

    public function hookActionAdminControllerSetMedia()
    {
        if (Tools::getValue('controller') === 'AdminAttributeFeatureConnector') {
            $this->context->controller->addJS($this->_path.'views/js/admin.js');
            $this->context->controller->addCSS($this->_path.'views/css/admin.css');
        }
    }
}