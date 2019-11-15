<?php

class TBT_Milestone_Model_Adapter_Special_Earned extends TBT_Milestone_Model_Adapter_Special_Abstract
{
    /**
     * @see TBT_Milestone_Model_Adapter_Special_Abstract::getConditionClassName()
     */
    public function getConditionClassName()
    {
        return 'points_earned';
    }

    public function getConditionLabel()
    {
        return  Mage::helper('tbtmilestone')->__("Reaches milestone for points earned");
    }

    public function getFieldLabel()
    {
        return  Mage::helper('tbtmilestone')->__("Points Earned");
    }

    public function getFieldComments()
    {
        $configLink = Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/rewards/') . '#rewards_milestone-head';

        if (Mage::helper('tbtmilestone/config')->doIncludePendingTransfers()) {
            $conditionText = "<strong>Pending and Approved</strong> transfers";
        } else {
            $conditionText = "<strong>Approved</strong> transfers only";
        }

        return "
            This includes {$conditionText}.<br/>
            <i>You can <a href='{$configLink}' target='_blank'>change this setting</a>.</i>
        ";
    }
}

