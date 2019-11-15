<?php

/**
 * Customer Cron Model that handles points notification emails
 *
 */
class TBT_Rewards_Model_Customer_Cron extends Varien_Object
{
    const XML_PATH_POINT_SUMMARY_EMAIL_TEMPLATE             = 'rewards/pointSummaryEmails/point_summary_email_template';
    const XML_PATH_POINT_SEND_NO_POINT                      = 'rewards/pointSummaryEmails/send_email_with_no_points';
    const XML_PATH_POINT_SEND_CUSTOMER_GROUP                = 'rewards/pointSummaryEmails/customer_group';
    const XML_PATH_POINT_SEND_EMAILS                        = 'rewards/pointSummaryEmails/allow_points_summary_email';
    const XML_PATH_POINT_SUMMARY_LAST_EXECUTION_TIME        = 'rewards/pointSummaryEmails/last_execution_time';
    
    protected $_canUseIndex;

    /**
     * Customer Cron Model that handles points notification emails
     */
    public function sendPointNotifications()
    {
        if (!Mage::getStoreConfigFlag(self::XML_PATH_POINT_SEND_EMAILS)) {
            return $this;
        }
         
        $now = time();
        $lastExecuted = intval(Mage::getStoreConfig(self::XML_PATH_POINT_SUMMARY_LAST_EXECUTION_TIME));
        if (!empty($lastExecuted) && $now - $lastExecuted < 24 * 60 * 60) {
            return $this; /* Already executed less than 24 hours ago, so skip */
            
        } else {
            Mage::getConfig()->saveConfig(self::XML_PATH_POINT_SUMMARY_LAST_EXECUTION_TIME, $now);
            Mage::getConfig()->reinit();
            Mage::app()->reinitStores();
        }
        
        $customerCollection = Mage::getModel('rewards/customer')
                ->getCollection()
                ->addNameToSelect()
                ->addAttributeToFilter('rewards_points_notification', array (
                    array('eq' => 1),
                    array('null' => true)
                ), 'left');
        
        if ($this->canUseIndex()) {
            $customerCollection->getSelect()
                ->joinInner(
                    array('rewards' => Mage::getSingleton('core/resource')->getTableName('rewards_customer_index_points')),
                    'e.entity_id = rewards.customer_id'
                );
        }
        
        $customerCollection->setPageSize(100);
        $pages = $customerCollection->getLastPageNumber();
        $currentPage = 1;
        
        do {
            $customerCollection->setCurPage($currentPage);
            $customerCollection->load();

            foreach ($customerCollection as $customer) {
                if (!$this->canUseIndex()) {
                    $customer = Mage::getModel('rewards/customer')->load($customer->getId());
                } else {
                    $customer->loadPointsBalance();
                }
                
                if ($this->isValidSendPointNotifications($customer)) {
                    $this->_sendEmail($customer);
                }
            }
            
            $currentPage++;
            $customerCollection->clear();
        } while ($currentPage <= $pages);
             
        return $this;
    }

    /**
     * Send out Point Summary Notification Email
     *
     * @param TBT_Rewards_Model_Customer $customer
     * @return boolean send successful?
     */
    private function _sendEmail($customer)
    {
        $template = Mage::getStoreConfig(self::XML_PATH_POINT_SUMMARY_EMAIL_TEMPLATE, $customer->getStoreId());

        /* @var $translate Mage_Core_Model_Translate */
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        $emailHelper = Mage::helper('rewards/email');
        $helper = Mage::helper('rewards');
        
        $sender = array(
            'name' => strip_tags(Mage::helper('rewards/expiry')->getSenderName($customer->getStoreId())),
            'email' => strip_tags(Mage::helper('rewards/expiry')->getSenderEmail($customer->getStoreId()))
        );

        $vars = array(
            'customer_name' => $customer->getName(),
            'customer_email' => $customer->getEmail(),
            'store_name' => $customer->getStore()->getFrontendName(),
            'points_balance' => (string) $helper->getPointsString($customer->getCustomerPointsUsable()),
            'pending_points' => (string) $helper->getPointsString($customer->getCustomerPointsPendingEvent()),
            'has_pending_points' => ($customer->getCustomerPointsPendingEvent() > 0)
        );
        
        if ($this->canUseIndex()) {
            $vars['points_balance'] = (string) $helper->getPointsString($customer->getCustomerPointsUsable());
            $vars['pending_points'] = (string) $helper->getPointsString($customer->getCustomerPointsPendingEvent());
            $vars['has_pending_points'] = ($customer->getCustomerPointsPendingEvent() > 0);
        } else {
            $vars['points_balance'] = (string) $customer->getPointsSummary();
            $vars['pending_points'] = (string) $customer->getPendingPointsSummary();
            $vars['has_pending_points'] = $customer->hasPendingPoints();
        }
        
        $result = $emailHelper->sendTransactional($template, $sender, $customer, $vars);
        $translate->setTranslateInline(true);

        return $result;
    }

    /**
     * Check customer is valid to send point notifications
     *
     * @param TBT_Rewards_Model_Customer $customer
     * @return boolean
     */
    public function isValidSendPointNotifications(TBT_Rewards_Model_Customer $customer)
    {
        return ( $this->isValidCustomerGroup($customer)
                && $this->isValidSendNoPoint($customer) );
    }

    /**
     * Check if current customer group is valid to receive points summary notification email
     *
     * @param TBT_Rewards_Model_Customer $customer
     * @return boolean
     */
    public function isValidCustomerGroup(TBT_Rewards_Model_Customer $customer)
    {
        $allowGroups = Mage::getStoreConfig(self::XML_PATH_POINT_SEND_CUSTOMER_GROUP, $customer->getStoreId());

        $allowGroupsArr = explode(",", $allowGroups);
        $isCusGrouoAvailable = in_array($customer->getGroupId(), $allowGroupsArr);

        return $isCusGrouoAvailable;
    }

    /**
     * Checks if customer should receive points summary notification email based
     * on config option 'Send Email To Users With No Points'. If both, customer's
     * points summary and pending points are zero and option set to NO, won't
     * receive notification.
     *
     * @param TBT_Rewards_Model_Customer $customer
     * @return boolean
     */
    public function isValidSendNoPoint(TBT_Rewards_Model_Customer $customer)
    {
        $noPointStr = Mage::helper ( 'rewards' )->getPointsString (array());

        if (
            !Mage::getStoreConfigFlag(self::XML_PATH_POINT_SEND_NO_POINT, $customer->getStoreId())
            && $customer->getPointsSummary() == $noPointStr
        ) {
            return false;
        }

        return true;
    }
    
    /**
     * Can use points indexer?
     * @return bool
     */
    protected function canUseIndex()
    {
        if (is_null($this->_canUseIndex)) {
            $this->_canUseIndex = Mage::helper('rewards/customer_points_index')->useIndex();
        }
        
        return $this->_canUseIndex;
    }
}

