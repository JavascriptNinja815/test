<?php

/**
 * @see TBT_Common_Model_Resource_Mysql4_Setup
 */
class TBT_Rewards_Model_Mysql4_Setup extends TBT_Common_Model_Resource_Mysql4_Setup
{
    /**
     * Runs after additional data update scripts have been executed
     * @return TBT_Rewards_Model_Mysql4_Setup
     */
    protected function _postApplyData()
    {
        Mage::helper('rewards/tracking')->track(TBT_Rewards_Helper_Tracking::EVENT_INSTALL);
        
        parent::_postApplyData();
        $this->_updateVersionInfo();
        
        return $this;
    }

    /**
     * If this store is connected to a Platform account, this method will send the latest
     * version information about Magento and Sweet Tooth up to Platform.
     * @return TBT_Rewards_Model_Mysql4_Setup
     */
    protected function _updateVersionInfo()
    {
        $apiKey = Mage::getStoreConfig('rewards/platform/apikey');
        if (!$apiKey) {
            return $this;
        }

        $channelData['channel_type'] = 'magento';
        $channelData['channel_version'] = (string) Mage::getConfig()->getNode('modules/TBT_Rewards/version');
        $channelData['platform_version'] = Mage::getVersion();
        $channelData['frontend_url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        $channelData['backend_url'] = Mage::getUrl('adminhtml');

        try {
            $platform = Mage::getModel('rewards/platform_instance');
            $platform->channel()->update($channelData);
        } catch (Exception $ex) {

        }

        return $this;
    }

    /**
     * This method will create a backend notification regarding a successful
     * Sweet Tooth installation, with the appropriate version number.
     * @return TBT_Rewards_Model_Mysql4_Setup
     */
    protected function _createSuccessfulUpdateNotice()
    {
        $version = Mage::getConfig()->getNode('modules/TBT_Rewards/version');
        $msgTitle = "MageRewards was successfully updated to v{$version}!";
        $msgDesc = "MageRewards was successfully updated to v{$version} on your store.";
        $this->createInstallNotice($msgTitle, $msgDesc);

        return $this;
    }

    /**
     * This method will create a backend notification regarding a successful
     * MageRewards installation, with the appropriate version number.
     * @return TBT_Rewards_Model_Mysql4_Setup
     */
    protected function _createSuccessfulInstallNotice()
    {
        $version = Mage::getConfig()->getNode('modules/TBT_Rewards/version');
        $msgTitle = "MageRewards v{$version} was successfully installed!";
        $msgDesc = "MageRewards v{$version} was successfully installed on your store.";
        $this->createInstallNotice($msgTitle, $msgDesc);

        return $this;
    }
    
    /**
     * @return TBT_Rewards_Model_Mysql4_Setup
     * @see TBT_Common_Model_Resource_Mysql4_Setup::applyUpdates()
     */
    public function applyUpdates()
    {
        $dbVersion = $this->_getResource()->getDbVersion($this->_resourceName);
        $configVersion = (string)$this->_moduleConfig->version;
        
        /* Disregard if database version is above or equal to 1.9 or config version is below 1.9 */
        if (version_compare($dbVersion, '1.9.0', '>=') || version_compare($configVersion, '1.9.0', '<')) {
            return parent::applyUpdates();
        }
        
        $table = Mage::getSingleton('core/resource')->getTableName('rewards/transfer');
        $transfersTableExists = Mage::getSingleton('core/resource')->getConnection('core_write')->isTableExists($table);
        if (!$transfersTableExists) {
            return parent::applyUpdates();
        }
        
        $transfersCount = Mage::getModel('rewards/transfer')->getCollection()->getSize();
        $cookie = Mage::getModel('core/cookie')->get('st_1900_install_confirm');

        if ($transfersCount > 0 && !$cookie) {
            if (!Mage::registry('st_redirect_to_confirmation_page')) {
                Mage::register('st_redirect_to_confirmation_page', true);
            }
            
            if (!$dbVersion && !Mage::registry('st_disable_update_scripts')) {
                Mage::register('st_disable_update_scripts', true);
            }

            /* Don't run the install script before confirmation */
            return $this;
        }
        
        return parent::applyUpdates();
    }
    
    /**
     * @return TBT_Rewards_Model_Mysql4_Setup
     * @see TBT_Common_Model_Resource_Mysql4_Setup::applyDataUpdates()
     */
    public function applyDataUpdates()
     {
        if (Mage::registry('st_redirect_to_confirmation_page')) {
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();

            /* Redirect for admin login and update confirmation - if we are not already on that page */
            if (strpos($currentUrl, 'rewards/update/index') === false) {
                Mage::getConfig()->saveConfig('rewards/migration/last_url', $currentUrl);
                Mage::app()->cleanCache();

                $url = Mage::getUrl('rewards/update/index', array('_secure' => true));
                Mage::app()->getResponse()->setRedirect($url)->sendResponse();
                
                $action = Mage::app()->getRequest()->getActionName();
                Mage::app()->getFrontController()->getAction()->setFlag($action, Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                
                return $this;
            }
        }
        
        if (!Mage::registry('st_disable_update_scripts')) {
            return parent::applyDataUpdates();
        }
        
        return $this;
    }
}

