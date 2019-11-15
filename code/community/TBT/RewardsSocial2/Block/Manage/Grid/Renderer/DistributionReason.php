<?php

class TBT_RewardsSocial2_Block_Manage_Grid_Renderer_DistributionReason extends TBT_Rewards_Block_Manage_Grid_Renderer_DistributionReason
{
    /**
     * This renders a column which holds a value that is a combination of 'reference_type' and 'reason_id' like this:
     * 'reference_type' . '_' . 'reason_id'
     * Based on this, it renders proper caption that identifies the reason for this points transfer.
     *
     * @param  Varien_Object $row
     * @return string
     * @see TBT_Rewards_Block_Manage_Grid_Renderer_DistributionReason::render();
     */
    public function render(Varien_Object $row)
    {
        $referenceTypeReason = $this->_getValue($row);
        if (!$referenceTypeReason) {
            return $referenceTypeReason;
        }
        $parts = explode('_', $referenceTypeReason);
        if (isset($parts[1])) {
            $referenceTypeId = $parts[0];
            $reasonId        = $parts[1];
        } else {
            $reasonId = array_shift($parts);
        }

        // if we can identify caption by transfer's 'reference_type' use this, except if 
        // it's a referral or social transfer in which case refine by it's reason
        if (
            isset($referenceTypeId) 
            && ($captionByReference = Mage::getModel('rewards/transfer_reference')->getReferenceCaption($referenceTypeId))
            && $referenceTypeId != 20
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
