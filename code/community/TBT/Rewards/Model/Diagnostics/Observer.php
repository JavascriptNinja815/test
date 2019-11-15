<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 *      https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL:
 *      http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, Sweet Tooth is
 * not held liable for any inconsistencies or abnormalities in the
 * behaviour of this code.
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Sweet Tooth, outlined in the
 * provided Sweet Tooth License.
 * Upon discovery of modified code in the process of support, the Licensee
 * is still held accountable for any and all billable time Sweet Tooth spent
 * during the support process.
 * Sweet Tooth does not guarantee compatibility with any other framework extension.
 * Sweet Tooth is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy
 * immediately.
 *
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Diagnostics Observer Class
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Diagnostics_Observer extends Varien_Object
{

    /**
     * This observer method fires on the event that the Sweet Tooth Rewards
     * DiagnosticsController is used to run the reinstall database script.
     *
     * After the rewards database has had a chance to rebuild then call the reinstall db action
     * for the platinum diagnostics
     */
    public function reinstallDb(Varien_Event_Observer $observer) 
    {
        Mage::getSingleton('rewards/diagnostics_database')->reinstallDb();
        return $observer;
    }

    public function onPreDispatch(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
		$controller = $event->getControllerAction();
        $this->_onPreDispatch($controller);

        return $this;
    }

    /**
     * On PreDispatch Actions
     * @param string $controller
     * @return \TBT_Rewards_Model_Diagnostics_Observer
     */
    public function _onPreDispatch($controller) 
    {
        if (!$this->_isManageControl($controller)) {
            return $this;
        }

        $this->checkEnterpriseReward();
        $this->checkMagePartsCEM();
        $this->addQuickLaunchNotice();
        $this->addTroubleshootingNotices();
        
        return $this;
    }
    
    /**
     * Display troubleshooting notifications
     */
    public function addTroubleshootingNotices()
    {
        $testsweetUrl = Mage::helper('adminhtml')->getUrl('adminhtml/testsweet/index');
        $helper = Mage::helper('rewards');

        if (Mage::getStoreConfig('rewards/notifications/extension_conflicts_found')) {
            $articleUrl = 'http://support.magerewards.com/article/1548-resolving-extension-conflicts';
            $message = $helper->__(
                "A module conflict was detected. "
                . "<a href='{$testsweetUrl}' title='Run Testsweet'>Run our diagnostics tool</a> for more details "
                . "or check our article on <a href='{$articleUrl}' title='Resolving Extension Conflicts' target='_blank'>how to solve extension conflicts.</a>"
            );
                
            Mage::getSingleton("adminhtml/session")->addError($message);
            Mage::getConfig()->saveConfig('rewards/notifications/extension_conflicts_found', null);
            Mage::app()->getCacheInstance()->cleanType('config');
        }
        
        if (Mage::getStoreConfig('rewards/notifications/database_structure_incoherent')) {
            $dbReinstallUrl = Mage::helper('adminhtml')->getUrl('adminhtml/manage_diagnostics/reinstallDb');
            $message = $helper->__(
                "The database structure is not as expected. "
                . "Try to <a href='{$dbReinstallUrl}' title='Reinstall Database'>run the installation scripts again</a> or to "
                . "<a href='{$testsweetUrl}' title='Run Testsweet'>run our diagnostics tool</a> for more details."
            );
                
            Mage::getSingleton("adminhtml/session")->addError($message);
            Mage::getConfig()->saveConfig('rewards/notifications/database_structure_incoherent', null);
            Mage::app()->getCacheInstance()->cleanType('config');
        }
    }
    
    /**
     * Add quick launch notification
     */
    public function addQuickLaunchNotice()
    {
        if (!Mage::helper('rewards')->storeHasRewardRules()) {
            $quickLaunchUrl = Mage::helper("adminhtml")->getUrl('adminhtml/rewardsDashboard/index');
            $notice = Mage::helper('rewards')->__("You can now use the %sQuick Launch%s feature to create basic loyalty rules and publish your campaign in seconds!","<a href='{$quickLaunchUrl}' title='Quick Launch'>",'</a>');
            Mage::getSingleton("adminhtml/session")->addNotice($notice);
        }
    }

    /**
     * Checking MageParts_CEM module is installed.
     *
     * if file has write permision then it will automatically
     * disable unless show massage.
     *
     * @return TBT_Rewards_Model_Diagnostics_Observer
     */
    protected function checkMagePartsCEM()
    {
        if( !$this->_hasModuleEnabled('MageParts_CEM')
                || Mage::getStoreConfigFlag('rewards/checkedPackage/MageParts_CEM')
                || $this->hasDataMagePartsCEM() ) {
             return $this;
        }

        if(Mage::helper('rewards/config')->disableModule('MageParts_CEM') ) {
            Mage::getSingleton('adminhtml/session')->addNotice("We detected that you have MageParts CEM extension installed on your store. MageRewards no longer supports this install/update method.<br />We disabled this extension for you to avoid any conflicts.");
        } else {
            Mage::getSingleton('adminhtml/session')->addError("We detected that you have MageParts CEM extension installed on your store. MageRewards no longer supports this install/update method.<br />To avoid any conflicts and make sure MageRewards is functioning properly, please disable this extension by changing the 'active' tag in app/etc/modules/MageParts_CEM.xml to 'false'.");
        }

        Mage::getConfig()->saveConfig('rewards/checkedPackage/MageParts_CEM', 1);// checked

        return $this;
    }

    /**
     * Checks if there are Mage Parts CEM
     * @return boolean
     */
    protected function hasDataMagePartsCEM()
    {
         return (Mage::getModel('cem/packages')->getCollection()->getSize() > 0 );
    }

    /**
     * Checks if Enterprise Rewards module is enabled and prints a notice
     * @return \TBT_Rewards_Model_Diagnostics_Observer
     */
    protected function checkEnterpriseReward() 
    {
        if (!$this->_hasModuleEnabled('Enterprise_Reward')) {
            return $this;
        }

        if (Mage::helper('rewards/config')->disableModule('Enterprise_Reward') ) {
            Mage::getSingleton('adminhtml/session')->addNotice("You had the Magento Enterprise default Rewards module enabled while MageRewards is enabled and installed so we disabled it for you.");
        } else {
            Mage::getSingleton('adminhtml/session')->addError("You have the Magento Enterprise default Rewards module enabled. MageRewards may not function as expected until you disable it. To disable it, change the 'active' tag in app/etc/modules/Enterprise_Reward.xml to 'false'.");
        }

        return $this;
    }

    /**
     * Checks if a module by key is enabled
     * @param string $module_key
     * @return boolean
     */
    protected function _hasModuleEnabled($module_key) 
    {
        $mod_cfg = Mage::getConfig ()->getModuleConfig ( $module_key );
        
        if (!$mod_cfg) {
            return false;
        }
        
        $is_active = $mod_cfg->is('active', true);
        return $is_active;
    }

    /**
     * @return true if the controller is of TBT_Rewards_Manage type
     */
    protected function _isManageControl($controller) 
    {
        $manage_controls = array(
            'TBT_Rewards_Manage_'
        );
        $class_name =  get_class($controller);
        foreach($manage_controls as $manage_control) {
            if(strpos($class_name, $manage_control) !== false) {
                return true;
            }
        }

        return false;
    }
}
