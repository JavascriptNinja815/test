<?php

class TBT_RewardsSocial2_Model_Reason_FacebookShare extends TBT_Rewards_Model_Transfer_Reason_Abstract
{
    const REASON_TYPE_ID = 81;

    public function getDistributionReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__('Customer shared a link on Facebook')
        );
    }

    public function getAllReasons() {
        return array(
            self::REASON_TYPE_ID => Mage::helper('rewardssocial2')->__('Facebook Share')
        );
    }
}