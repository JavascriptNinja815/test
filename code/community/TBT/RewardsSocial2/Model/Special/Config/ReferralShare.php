<?php

class TBT_RewardsSocial2_Model_Special_Config_ReferralShare extends TBT_Rewards_Model_Special_Configabstract
{
    const ACTION_CODE = 'social_referral_share';

    public function getNewCustomerConditions()
    {
        return array(
            self::ACTION_CODE => Mage::helper('rewardssocial2')->__("Shares their referral link")
        );
    }

    public function getNewActions()
    {
        return array ();
    }
}
