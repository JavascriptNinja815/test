<?php

class TBT_RewardsSocial2_Model_Special_Config_FacebookLike extends TBT_Rewards_Model_Special_Configabstract
{
    const ACTION_CODE = 'social_facebook_like';
    
    public function getNewCustomerConditions()
    {
        return array(
            self::ACTION_CODE => Mage::helper('rewardssocial2')->__("Likes a page with Facebook")
        );
    }
    
    public function getNewActions()
    {
        return array();
    }
}
