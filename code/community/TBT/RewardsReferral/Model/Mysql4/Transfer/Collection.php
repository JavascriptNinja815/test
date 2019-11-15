<?php

class TBT_RewardsReferral_Model_Mysql4_Transfer_Collection extends TBT_Rewards_Model_Mysql4_Transfer_Collection
{    
    /**
     * Get the accumulated points earned from this referral object
     * 
     * @param TBT_RewardsReferral_Model_Referral $referral
     * @return TBT_Rewards_Model_Points
     */
    public function getAccumulatedPoints($referral)
    {
        $pointsEarned = Mage::getModel('rewards/points');
        
        $collection = $this->filterReferralTransfers($referral->getReferralChildId())
            ->addFieldToFilter('status_id', TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED)
            ->addFieldToFilter('customer_id', $referral->getReferralParentId())
            ->selectOnlyPosTransfers()
            ->sumPoints()
            ->load();
        
        $defaultCurrencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
        foreach ($collection as $points) {
            $pointsEarned->add($defaultCurrencyId, (int)$points->getPointsCount());
        }
        
        return $pointsEarned;
    }
    
    /**
     * Get the accumulated pending points earned from this referral object
     * @param TBT_RewardsReferral_Model_Referral $referral
     * @return TBT_Rewards_Model_Points
     */
    public function getPendingReferralPoints($referral)
    {
        $pointsEarned = Mage::getModel('rewards/points');
        
        $collection = $this->filterReferralTransfers($referral->getReferralChildId())
            ->addFieldToFilter('customer_id', $referral->getReferralParentId())
            ->addFieldToFilter('status_id', array('in' => array(
                TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_APPROVAL,
                TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT,
                TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_TIME
            )))
            ->selectOnlyPosTransfers()
            ->sumPoints()
            ->load();

        $defaultCurrencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
        foreach ($collection as $points) {
            $pointsEarned->add($defaultCurrencyId, (int)$points->getPointsCount());
        }
        
        return $pointsEarned;
    }
    
    /**
     * Filter transfers to include only referral entries
     * @param int $entityId (customer or order ID)
     * @param bool $searchOrders
     */
    public function filterReferralTransfers($entityId, $searchOrders = true) 
    {
        $helper = Mage::helper('rewards/transfer_reason');

        // Add customer ID
        $referenceIds = array($entityId);
        
        // Add order IDs
        if ($searchOrders) {
            $orderIds = Mage::getResourceModel('sales/order_collection')
                ->addFieldToFilter('customer_id', $entityId)
                ->getAllIds();
            
            $referenceIds = array_merge($referenceIds, $orderIds);
        }
        
        $this->addFieldToFilter('reference_id', array('in' => $referenceIds))
            ->addFieldToFilter('reason_id', array('in' => array(
                $helper->getReasonId('referral_order_first'),
                $helper->getReasonId('referral_order'),
                $helper->getReasonId('referral_order_guest'),
                $helper->getReasonId('referral_signup')
            )));
        
        return $this;
    }
}
