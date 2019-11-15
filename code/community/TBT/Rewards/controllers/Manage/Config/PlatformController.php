<?php

class TBT_Rewards_Manage_Config_PlatformController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('rewards');
    }
    
    public function disconnectAction()
    {
        Mage::register('st_disable_rule_save_tracking', true);
        Mage::getConfig()->saveConfig(TBT_Rewards_Helper_Platform_Account::XPATH_PLATFORM_USERNAME, '');
        Mage::getConfig()->saveConfig(TBT_Rewards_Helper_Platform_Account::XPATH_PLATFORM_EMAIL, '');
        Mage::getConfig()->saveConfig('rewards/platform/password', '');
        Mage::getConfig()->saveConfig(TBT_Rewards_Helper_Platform_Account::XPATH_PLATFORM_FIRSTNAME, '');
        Mage::getConfig()->saveConfig(TBT_Rewards_Helper_Platform_Account::XPATH_PLATFORM_LASTNAME, '');

        Mage::getConfig()->saveConfig('rewards/platform/apikey', '');
        Mage::getConfig()->saveConfig('rewards/platform/secretkey', '');
        Mage::getConfig()->saveConfig('rewards/platform/apisubdomain', '');

        Mage::getConfig()->saveConfig('rewards/platform/is_connected', 0);
        Mage::getConfig()->saveConfig('rewards/platform/is_signup', 0);
        Mage::getConfig()->saveConfig('rewards/platform/dev_mode', 0);

        Mage::getConfig()->cleanCache();

        Mage::getSingleton('core/session')->addSuccess('Successfully disconnected your MageRewards account.');

        $this->_keepPlatformDetailsSectionOpen();

        $this->_redirect('adminhtml/system_config/edit', array('section' => 'rewards'));
        return $this;
    }

    public function connectAction()
    {
        Mage::getConfig()->saveConfig('rewards/platform/is_signup', 0);

        $username = $this->getRequest()->get('username');
        $password = $this->getRequest()->get('password');
        
        $isDevMode = $this->getRequest()->get('isDevMode');
        $isDevMode = ($isDevMode == 'on' || $isDevMode == 1) ? 1 : 0;

        Mage::register('st_disable_rule_save_tracking', true);
        Mage::getConfig()->saveConfig('rewards/platform/dev_mode', $isDevMode);
        Mage::getConfig()->cleanCache();

        Mage::helper('rewards/platform')->connectWithPlatformAccount($username, $password, $isDevMode);

        $this->_keepPlatformDetailsSectionOpen();

        $redirectToQuickLaunch = $this->getRequest()->get('redirect-to-quick-launch');
        
        $triggeredFrom = ($redirectToQuickLaunch) ? 'quicklaunch' : 'configuration';
        Mage::helper('rewards/tracking')->track(TBT_Rewards_Helper_Tracking::EVENT_CONNECT, array('triggered_from' => $triggeredFrom));
        
        if ($redirectToQuickLaunch == 1) {
            $this->_redirect('adminhtml/rewardsDashboard/index');
        } else {
            $this->_redirect('adminhtml/system_config/edit', array('section' => 'rewards'));
        }
        return $this;
    }

    public function signupAction()
    {
        Mage::getConfig()->saveConfig('rewards/platform/is_signup', 1);

        $username = $this->getRequest()->get('username');
        $email = $this->getRequest()->get('email');
        $password = $this->getRequest()->get('password');
        
        Mage::helper('rewards/platform')->createPlatformAccount($username, $email, $password);

        $this->_redirect('adminhtml/system_config/edit', array('section' => 'rewards'));
        return $this;
    }

    protected function _keepPlatformDetailsSectionOpen()
    {
        $adminUser = Mage::getSingleton('admin/session')->getUser();
        $extra = $adminUser->getExtra();

        if (!is_array($extra)) {
            $extra = array();
        }
        if (!isset($extra['configState'])) {
            $extra['configState'] = array();
        }

        $extra['configState']['rewards_platform'] = 1;
        $adminUser->saveExtra($extra);

        return $this;
    }
}
