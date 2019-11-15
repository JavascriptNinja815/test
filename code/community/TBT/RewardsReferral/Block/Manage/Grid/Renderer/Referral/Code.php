<?php

class TBT_RewardsReferral_Block_Manage_Grid_Renderer_Referral_Code extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Display referral code
     *
     * @param  Varien_Object $row
     * @return String code
     */
    public function render(Varien_Object $row)
    {
        return Mage::helper("rewardsref/code")->getCode($row->getReferralChildId());
    }
}