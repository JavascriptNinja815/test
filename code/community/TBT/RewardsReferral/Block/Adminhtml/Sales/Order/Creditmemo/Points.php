<?php

class TBT_RewardsReferral_Block_Adminhtml_Sales_Order_Creditmemo_Points extends Mage_Adminhtml_Block_Template
{
    protected $_transfers = null;

    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('rewardsref/sales/order/creditmemo/points.phtml');
        $this->setFieldWrapper('creditmemo');

        return $this;
    }

    /**
     * Retrieves sum of points earned by the affiliate for this order, first order
     * rule points (if any) + any order rule points
     * @return int Sum of points earned by the affiliate on this order
     */
    public function getAffiliatePointsEarned()
    {
        $points = 0;

        if (!$this->getOrder()) {
            return $points;
        }

        if (!$this->_transfers) {
            $orderId = $this->getOrder()->getId();
            $helper = Mage::helper('rewards/transfer_reason');
            
            $this->_transfers = Mage::getResourceModel('rewards/transfer_collection')
                ->selectOnlyPosTransfers()
                ->addFieldToFilter('reference_id', $orderId)
                ->addFieldToFilter('reason_id', array('in' => array(
                    $helper->getReasonId('referral_order_first'),
                    $helper->getReasonId('referral_order'),
                    $helper->getReasonId('referral_order_guest')
                ))
            );
        }

        foreach ($this->_transfers as $transfer) {
            $points += $transfer->getQuantity();
        }

        return $points;
    }

}
