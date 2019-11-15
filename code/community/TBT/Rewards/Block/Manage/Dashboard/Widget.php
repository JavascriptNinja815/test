<?php

class TBT_Rewards_Block_Manage_Dashboard_Widget extends Mage_Adminhtml_Block_Template
{
    const CACHE_TAG = 'rewards_dashboard_widget';

    protected $_account = null;

    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('rewards/dashboard/widget/dashboard.phtml');
        $this->addData(array(
            'cache_lifetime' => false,
            'cache_tags'     => array(self::CACHE_TAG)
        ));

        return $this;
    }

    /**
     * Checks if Sweet Tooth panel from Dashboard is enabled/disabled
     *
     * @return boolean
     */
    public function displayRewardsDashboard()
    {
        return Mage::helper('rewards/config')->displayRewardsDashboardWidget();
    }

    /**
     * Will count the number of children who make Ajax calls
     * @return int
     */
    public function countAjaxComponents()
    {
        $count = 0;
        foreach ($this->getChild() as $child) {
            if ($child->isAjaxComponent()) $count++;
        }

        return $count;
    }

}
