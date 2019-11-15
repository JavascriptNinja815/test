<?php

/**
 * Template class for any component that can go on the Sweet Tooth Dashboard Widget
 * on Magento's Dashboard page
 *
 * Class TBT_Rewards_Block_Manage_Dashboard_Template
 */
class TBT_Rewards_Block_Manage_Dashboard_Widget_Template extends Mage_Adminhtml_Block_Template
{
    const CONFIG_DEV_MODE = 'rewards/platform/dev_mode';

    /**
     * Get DOM class-name to render this block in
     * @return string
     */
    public function getDomClassName()
    {
        return "";
    }

    /**
     * Get Ajax template for this component
     * @return string
     */
    public function getAjaxTemplate()
    {
        return "";
    }

    /**
     * Get Ajax URL for this component
     * @return string
     */
    public function getAjaxUrl()
    {
        return "";
    }

    /**
     * Consider this an Ajax Component if there's an Ajax URL
     * as well as a Class Name for the container to render this in.
     *
     * @return bool
     */
    public function isAjaxComponent()
    {
        $ajaxUrl = $this->getAjaxUrl();
        $domClassName = $this->getDomClassName();
        if (!empty($ajaxUrl) && !empty($domClassName)) {
            return true;
        }
    }

    /**
     * @return string
     */
    public function getAjaxHtml()
    {
        $oldTemplate = $this->getTemplate();
        $this->setTemplate($this->getAjaxTemplate());
        $html = $this->toHtml();
        $this->setTemplate($oldTemplate);

        return $html;
    }

    /**
     * Check if account is connected and is in developer mode
     * @return boolean return true if account connected in Developer Mode
     */
    public function isDevMode()
    {
        $account = $this->getAccountData();
        return ($account !== false && Mage::getStoreConfig(self::CONFIG_DEV_MODE));
    }

    /**
     * @return TBT_Rewards_Helper_Datetime
     */
    public function getDateTimeHelper()
    {
        return Mage::helper('rewards/datetime');
    }
}