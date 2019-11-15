<?php

class TBT_RewardsSocial2_Model_Reason_PinterestPin extends TBT_Rewards_Model_Transfer_Reason_Abstract
{
    const REASON_TYPE_ID = 83;

    public function getDistributionReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__("Customer pinned an image on Pinterest")
        );
    }

    public function getAllReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__("Pinterest Pin")
        );
    }
}