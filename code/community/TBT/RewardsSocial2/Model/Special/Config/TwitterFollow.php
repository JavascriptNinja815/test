<?php

class TBT_RewardsSocial2_Model_Special_Config_TwitterFollow extends TBT_Rewards_Model_Special_Configabstract
{
    const ACTION_CODE = 'social_twitter_follow';

    public function getNewCustomerConditions()
    {
        return array(
            self::ACTION_CODE => Mage::helper('rewardssocial2')->__("Follows you on Twitter")
        );
    }

    public function getNewActions()
    {
        return array ();
    }
}
