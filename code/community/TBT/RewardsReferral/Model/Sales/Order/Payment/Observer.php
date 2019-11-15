<?php

class TBT_RewardsReferral_Model_Sales_Order_Payment_Observer extends TBT_Rewards_Model_Sales_Order_Payment_Observer
{
    /**
     * Cancel affiliate transfers
     * 
     * @param Varien_Event_Observer $observer
     * @return TBT_RewardsReferral_Model_Sales_Order_Creditmemo_Observer
     * @event controller_action_postdispatch_adminhtml_sales_order_cancel
     */
    public function automaticCancelAffiliate($observer)
    {
        $action = $observer->getControllerAction();

        if ($action) {
            $params = $action->getRequest()->getParams();
            $order = Mage::getModel('sales/order')->load($params['order_id']);
        } else {
            $payment = $observer->getEvent()->getPayment();

            if ($payment) {
                $order = $payment->getOrder();
            } else {
                $order = $observer->getEvent()->getOrder();
            }
        }
        
        if (!$order) {
            return $this;
        }

        if (Mage::helper ( 'rewards/config' )->shouldRemovePointsOnCancelledOrder ()) {

            $displayMessages = false;

            $affiliateTransfers = Mage::getResourceModel('rewardsref/transfer_collection')
                ->filterReferralTransfers($order->getId(), false);

            foreach ($affiliateTransfers as $affiliateTransfer) {
                if (($affiliateTransfer->getStatusId () == TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT) || ($affiliateTransfer->getStatusId () == TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_APPROVAL)) {
                    $affiliateTransfer->setStatusId ( $affiliateTransfer->getStatusId (), TBT_Rewards_Model_Transfer_Status::STATUS_CANCELLED );
                    $affiliateTransfer->save ();
                    $affiliateTransfer->setCanceled(1);
                    $displayMessages = true;
                } else if ($affiliateTransfer->getStatusId () == TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED) {
                    try {
                        // try to revoke the transfer and keep track of the new transfer ID to notify admin
                        $revokedTransferId = $affiliateTransfer->revoke()->getId();
                        $affiliateTransfer->setRevokedTransferId($revokedTransferId);
                        $displayMessages = true;
                    } catch ( Exception $ex ) {
                        $affiliateTransfer->setRevokedTransferId(null);
                        continue;
                    }
                } else if ($affiliateTransfer->getStatusId () == TBT_Rewards_Model_Transfer_Status::STATUS_CANCELLED) {
                    // transfer was already canceled (by admin, probably), so we just notify him
                    $affiliateTransfer->setCanceled(0);
                    $displayMessages = true;
                }
            }

            if ($displayMessages) {
                if ($this->_getRewardsSession ()->isAdminMode ()) {
                    // this means a mass admin order cancel operation made by administrator
                    $this->_displayAdminMessages($affiliateTransfers);
                }
            }

        }

        return $this;
    }

}