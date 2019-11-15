<?php

class TBT_RewardsSocial2_Model_Reason_PurchaseShareTwitter extends TBT_Rewards_Model_Transfer_Reason_Abstract
{
    const REASON_TYPE_ID = 85;

    public function getDistributionReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__("Customer tweeted a purchase on Twitter")
        );
    }

    public function getAllReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__("Purchase Share (Twitter)")
        );
    }
}