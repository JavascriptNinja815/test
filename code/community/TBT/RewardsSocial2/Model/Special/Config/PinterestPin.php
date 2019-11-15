<?php

class TBT_RewardsSocial2_Model_Special_Config_PinterestPin extends TBT_Rewards_Model_Special_Configabstract
{
    const ACTION_CODE = 'social_pinterest_pin';

    public function _construct()
    {
        return parent::_construct();
    }

    public function getNewCustomerConditions()
    {
        return array(
            self::ACTION_CODE => Mage::helper('rewardssocial2')->__("Pins a page with Pinterest")
        );
    }

    public function getNewActions()
    {
        return array ();
    }
}
