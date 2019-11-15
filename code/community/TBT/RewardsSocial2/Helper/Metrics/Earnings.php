<?php

class TBT_RewardsSocial2_Helper_Metrics_Earnings extends TBT_Rewards_Helper_Metrics_Earnings
{
    /**
     * This accepts a parameter like 'reference_type' . '_' . 'reason_id' and based on this returns a caption that
     * clearly outlines the reason for this points transfer.
     *
     * @param  string $referenceTypeReasonId
     * @return string
     * @see TBT_Rewards_Helper_Metrics_Earnings::getReasonCaption()
     */
    public function getReasonCaption($referenceTypeReasonId)
    {
        if (!$referenceTypeReasonId) {
            return $referenceTypeReasonId;
        }
        $parts = explode('_', $referenceTypeReasonId);
        if (isset($parts[1])) {
            $referenceTypeId = $parts[0];
            $reasonId        = $parts[1];
        } else {
            $reasonId = array_shift($parts);
        }

        // if we can identify caption by transfer's 'reference_type' use this, except if it's a referral transfer in
        // which case refine by it's reason
        if (
            isset($referenceTypeId) 
            && ($captionByReference = Mage::getModel('rewards/transfer_reference')->getReferenceCaption($referenceTypeId))
            && $referenceTypeId != TBT_RewardsReferral_Model_Transfer_Reference_Referral::REFERENCE_TYPE_ID
            && $referenceTypeId != TBT_RewardsSocial2_Model_Transfer_Reference::REFERENCE_TYPE_ID
        ) {
            return $captionByReference;
        }

        if (isset($reasonId) && $captionByReason = Mage::getModel('rewards/transfer_reason')->getReasonCaption($reasonId)) {
            return $captionByReason;
        }

        return Mage::helper('rewards')->__('Other');
    }
}
