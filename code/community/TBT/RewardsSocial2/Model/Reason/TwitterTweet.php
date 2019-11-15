<?php

class TBT_RewardsSocial2_Model_Reason_TwitterTweet extends TBT_Rewards_Model_Transfer_Reason_Abstract
{
    const REASON_TYPE_ID = 88;

    public function getDistributionReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__("Customer tweeted about us on Twitter")
        );
    }

    public function getAllReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__("Twitter Tweet")
        );
    }
}