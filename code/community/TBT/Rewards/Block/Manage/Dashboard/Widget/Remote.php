<?php

class TBT_Rewards_Block_Manage_Dashboard_Widget_Remote extends TBT_Rewards_Block_Manage_Dashboard_Widget_Template
{
    const REMOTE_URL_PATH = '/rewardsplatform/remote/dashboardWidget';

    /**
     * Get DOM class-name to render this block in
     * @return string
     */
    public function getDomClassName()
    {
        return "st-remote";
    }

    /**
     * Get Ajax URL for this component
     * @return string
     */
    public function getAjaxUrl()
    {
        $remoteUrl = $this->helper('rewards/platform_remote')->getDashboardWidgetUrl();
        if (is_null($remoteUrl)) {
            $remoteUrl = "";
        }

        return $remoteUrl;
    }

}
