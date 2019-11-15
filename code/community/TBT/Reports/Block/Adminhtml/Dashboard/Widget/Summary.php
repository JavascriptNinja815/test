<?php

class TBT_Reports_Block_Adminhtml_Dashboard_Widget_Summary extends TBT_Rewards_Block_Manage_Dashboard_Widget_Template
{

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        /**
         * Before we render anything, check if summary is available,
         * otherwise display a warning message in the Sweet Tooth widget.
         */
        if (!$this->isSummaryReady()) {
            $dashboardNotificationBlock = $this->getLayout()->getBlock('rewards_dashboard_widget_notifications');
            if ($dashboardNotificationBlock instanceof TBT_Rewards_Block_Manage_Dashboard_Widget_Notifications) {
                $notificationLink = Mage::getBlockSingleton('index/adminhtml_notifications')->getManageUrl();
                $notificationLinkText = $this->__('Index Management');
                $notification = $this->__("Please rebuild required indexes to see %s metrics: ", "MageRewards");
                $dashboardNotificationBlock->addNotification($notification, $notificationLink, $notificationLinkText);
            }
        }

        return $this;
    }

    public function getDomClassName()
    {
        return "tbtreports-summary";
    }

    public function getAjaxTemplate()
    {
        return "tbtreports/adminhtml/dashboard/widget/summary.phtml";
    }

    /**
     * @return string|null
     */
    public function getAjaxUrl()
    {
        /**
         * Ajax only available if summary is available
         */
        $url = null;
        if ($this->isSummaryReady()) {
            $url = $this->getUrl(
                'adminhtml/adminhtml_dashboard/summaryAjax',
                array(
                    '_forced_secure' => $this->getRequest()->isSecure()
                )
            );
        }

        return $url;
    }

    public function getDashboardUrl()
    {
        return $this->getUrl('adminhtml/rewardsDashboard/index');
    }

    /**
     * @return TBT_Reports_Model_Metrics_Abstract
     */
    public function getFirstMetric()
    {
        return Mage::getModel('tbtreports/metrics_loyaltyCustomersRevenue');
    }

    /**
     * @return TBT_Reports_Model_Metrics_Abstract
     */
    public function getSecondMetric()
    {
        return Mage::getModel('tbtreports/metrics_referredCustomersRevenue');
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
     * @return TBT_Reports_Helper_Data
     */
    protected function _helper()
    {
        return Mage::helper('tbtreports');
    }
}