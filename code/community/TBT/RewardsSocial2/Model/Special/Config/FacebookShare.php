<?php

class TBT_RewardsSocial2_Model_Special_Config_FacebookShare extends TBT_Rewards_Model_Special_Configabstract
{
    const ACTION_CODE = 'social_facebook_share';

    public function getNewCustomerConditions()
    {
        return array(
            self::ACTION_CODE => Mage::helper('rewardssocial2')->__("Shares a product on Facebook")
        );
    }

    public function getNewActions()
    {
        return array ();
    }
}
