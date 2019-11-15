<?php

class TBT_Rewards_Block_Manage_Dashboard_Widget_Notifications extends TBT_Rewards_Block_Manage_Dashboard_Widget_Template
{
    /**
     * Notifications array
     * @var array
     */
    protected $_notifications = array();

    protected function _construct()
    {
        parent::_construct();
        if ($this->displayConnectNotification()) {
            $this->addNotification(
                $this->__("Connect your MageRewards Account to start rewarding!"),
                "http://support.magerewards.com/article/1551-connecting-a-magento-store-to-your-magerewards-account",
                $this->__("Learn More")
            );
        }
        if ($this->displayConnectNotification() && $this->_displayDisableNotification()) {
            $this->addNotification(
                $this->__("MageRewards will automatically stop rewarding your customers, if your account is disconnected for longer than 24 hours."),
                "http://support.magerewards.com/article/1551-connecting-a-magento-store-to-your-magerewards-account",
                $this->__("Learn More")
            );
        }
        if ($this->isDevMode()) {
            $this->addNotification(
                $this->__("Your account is in <strong>Developer Mode</strong>."),
                "http://support.magerewards.com/article/1557-developer-mode-in-magento",
                $this->__("Learn More")
            );
        }
        if ($this->displayCronNotification()) {
            $this->addNotification(
                $this->__("Your CRON tasks are not enabled and may be limiting functionality."),
                "http://support.magerewards.com/article/1533-setting-up-cron-jobs-in-magento",
                $this->__("Learn More")
            );
        }

    }


    
    /**
     * Retrieve all active notifications
     * 
     * @return array Contains all notifications
     */
    public function getNotifications()
    {
        return $this->_notifications;
    }
    
    /**
     * Add a new notification
     * @param text $notifText       The notification text displayed to the user
     * @param text $notifLink       The notification link
     * @param text $notifLinkText   The text to be displayed for the link
     */
    public function addNotification ($notifText, $notifLink, $notifLinkText, $target='_blank')
    {
        $notification = array(
            'text'      => $notifText,
            'link'      => $notifLink,
            'linkText'  => $notifLinkText,
            'target'    => $target
        );
        
        $this->_notifications[] = $notification;
        
        return $this;
    }

    /**
     * Checks whether we add a notification on Sweet Tooth Usage Dashboard about CRON not working
     * @return boolean returns true if CRON is not enabled
     */
    public function displayCronNotification()
    {
        if ( !$this->_isCronRequired() ) {
            return false;
        }

        return ! Mage::helper('rewards/cron')->isWorking();
    }


    public function displayConnectNotification()
    {
        $account = $this->getAccountData();

        if ($account === false) {
            return true;
        }

        return false;
    }

    /**
     * Checks whether or not there are any earning rules. These will be disabled if account is not connected
     * for more than 24 hours.
     *
     * @return bool     True, if there are earning rules, false otherwise.
     */
    protected function _displayDisableNotification()
    {
        $behaviourRulesCount = Mage::getResourceModel('rewards/special_collection')
            ->getSize();
        if ($behaviourRulesCount > 0) {
            return true;
        }

        $salesRulesCount = Mage::getResourceModel('salesrule/rule_collection')
            ->addFieldToFilter('points_action', array('notnull' => true))
            ->addFieldToFilter('points_discount_action', array('null' => true))
            ->getSize();
        if ($salesRulesCount > 0) {
            return true;
        }

        return false;
    }

    /*
     *  Check for all the cron dependent sweet tooth services
     *  return boolean
     */
    protected function _isCronRequired()
    {
        if (Mage::getStoreConfigFlag('rewards/expire/is_enabled')) {
            return true;
        }
        if (Mage::getStoreConfigFlag('rewards/display/allow_points_summary_email')) {
            return true;
        }
        if (Mage::helper('rewards/cron')->hasBirthdayPointRules()) {
            return true;
        }
        if (Mage::helper('rewards/cron')->hasOnholdRules()) {
            return true;
        }

        return false;
    }
}
