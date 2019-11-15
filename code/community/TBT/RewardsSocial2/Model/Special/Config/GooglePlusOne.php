<?php

class TBT_RewardsSocial2_Model_Special_Config_GooglePlusOne extends TBT_Rewards_Model_Special_Configabstract
{
    const ACTION_CODE = 'social_google_plusOne';

    public function _construct()
    {
        return parent::_construct();
    }

    public function getNewCustomerConditions()
    {
        return array(
            self::ACTION_CODE => Mage::helper('rewardssocial2')->__("+1's a page with Google+")
        );
    }

    public function getNewActions()
    {
        return array ();
    }
}
