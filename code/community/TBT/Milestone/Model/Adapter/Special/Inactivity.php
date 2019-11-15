<?php

class TBT_Milestone_Model_Adapter_Special_Inactivity extends TBT_Milestone_Model_Adapter_Special_Abstract
{
    public function getConditionLabel()
    {
        return  Mage::helper('tbtmilestone')->__("Reaches an inactivity period");
    }

    public function getFieldLabel()
    {
        return  Mage::helper('tbtmilestone')->__("Number of Inactive Days");
    }

    public function getFieldComments()
    {
        $comment = Mage::helper('tbtmilestone')->__("Magento's Cron must be functional for this rule.");
            
        if (Mage::helper('rewards/version')->isBaseMageVersionAtLeast('1.9.2.0')) {
            $configLink = Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/system");
            $comment .= Mage::helper("tbtmilestone")->__(
                " Also, customer activity logs must be fully enabled. You can activate the logs %shere%s. %sLearn more%s.",
                "<i><a href=\"{$configLink}\" target=\"_blank\">", 
                "</a></i>",
                "<i><a href=\"http://support.magerewards.com/article/1728-inactivity-milestone\" target=\"_blank\">",
                "</a></i>"
            );
        }
            
        return $comment;
    }
}

