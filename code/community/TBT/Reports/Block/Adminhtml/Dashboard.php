<?php

class TBT_Reports_Block_Adminhtml_Dashboard extends Mage_Adminhtml_Block_Template
{
    protected $_mainMetrics = array();
    protected $_groupMetrics = array();

    /**
     * Cached list of main metrics
     * @return array<TBT_Reports_Model_Metrics_Abstract>
     */
    public function getMainMetrics()
    {
        if (empty($this->_mainMetrics)) {
            $this->_mainMetrics = array(
                Mage::getModel('tbtreports/metrics_loyaltyCustomersRevenue'),
                Mage::getModel('tbtreports/metrics_referredCustomersRevenue')
            );
        }

        return $this->_mainMetrics;
    }

    /**
     * Get a list of metrics in specified group
     * @param $groupNumber
     * @return mixed
     */
    public function getGroupMetrics($groupNumber)
    {
        if (empty($this->_groupMetrics[$groupNumber])){
            switch ($groupNumber) {
                case 1:
                    $this->_groupMetrics[$groupNumber] = array(
/******
 * Introduce these later
 */
//                        Mage::getModel('tbtreports/metrics_social_facebookRewards'),
//                        Mage::getModel('tbtreports/metrics_social_twitterRewards'),
//                        Mage::getModel('tbtreports/metrics_social_googlePlusRewards'),
//                        Mage::getModel('tbtreports/metrics_social_pinterestRewards'),
                    );
                    break;
                default:
                    $this->_groupMetrics[$groupNumber] = array();
            }
        }

        return $this->_groupMetrics[$groupNumber];
    }

    /**
     * Will check to see if order indexer is ready
     * @return boolean
     */
    public function isSummaryReady()
    {
        return Mage::helper('tbtreports/indexer_order')->isReady();
    }

    /**
     * @return string
     */
    public function getNotReadyNotification()
    {
        $notificationLink = Mage::getBlockSingleton('index/adminhtml_notifications')->getManageUrl();
        $notificationLinkText = $this->__('Index Management');
        $notification = $this->__("Some metric data is not available until you've rebuilt missing %s indexes", "MageRewards");

        return "{$notification}: <a href='{$notificationLink}'>{$notificationLinkText}</a>";
    }

    /**
     * @return TBT_Rewards_Helper_Datetime
     */
    public function getDateTimeHelper()
    {
        return Mage::helper('rewards/datetime');
    }
}