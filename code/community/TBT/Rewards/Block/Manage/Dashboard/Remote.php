<?php

class TBT_Rewards_Block_Manage_Dashboard_Remote extends Mage_Adminhtml_Block_Template
{

    /**
     * @return bool|string remote url to dashboard contents. False if no platform login.
     */
    public function getRemoteUrl()
    {
        return $this->helper('rewards/platform_remote')->getDashboardUrl();
    }
}