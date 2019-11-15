<?php
class TBT_RewardsSocial2_Model_Special_Config_TwitterTweet extends TBT_Rewards_Model_Special_Configabstract
{
    const ACTION_CODE = 'social_twitter_tweet';
    
    public function getNewCustomerConditions()
    {
        return array(
            self::ACTION_CODE => Mage::helper('rewardssocial2')->__("Tweets about a page on Twitter")
        );
    }
    
    public function getNewActions()
    {
        return array ();
    }    
}
