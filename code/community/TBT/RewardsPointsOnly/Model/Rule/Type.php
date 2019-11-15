<?php

class TBT_RewardsPointsOnly_Model_Rule_Type
{
    const REWARDS_POINTONLY_DEDUCT_POINTS = 'deduct_points';

    const REWARDS_POINTSONLY_DEDUCT_BY_AMOUNT_SPENT = 'deduct_by_amount_spent';

    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('rewardspointsonly')->__('Deduct Points'),
                'value' => self::REWARDS_POINTONLY_DEDUCT_POINTS
            ),
            array(
                'label' => Mage::helper('rewardspointsonly')->__('Deduct by Amount Spent'),
                'value' => self::REWARDS_POINTSONLY_DEDUCT_BY_AMOUNT_SPENT
            )
        );
    }

    public function getOptions()
    {
        return array(
            self::REWARDS_POINTONLY_DEDUCT_POINTS,
            self::REWARDS_POINTSONLY_DEDUCT_BY_AMOUNT_SPENT
        );
    }
}