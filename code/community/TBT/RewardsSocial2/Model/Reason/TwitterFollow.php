<?php

class TBT_RewardsSocial2_Model_Reason_TwitterFollow extends TBT_Rewards_Model_Transfer_Reason_Abstract
{
    const REASON_TYPE_ID = 87;

    public function getDistributionReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__("Customer followed us on Twitter")
        );
    }

    public function getAllReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__("Twitter Follow")
        );
    }
}