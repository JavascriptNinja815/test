<?php

class TBT_RewardsSocial2_Model_Reason_ReferralShare extends TBT_Rewards_Model_Transfer_Reason_Abstract
{
    const REASON_TYPE_ID = 86;

    public function getDistributionReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__("Customer shared their referral link")
        );
    }

    public function getAllReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__("Referral Share")
        );
    }
}