<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 * https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, Sweet Tooth is
 * not held liable for any inconsistencies or abnormalities in the
 * behaviour of this code.
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Sweet Tooth, outlined in the
 * provided Sweet Tooth License.
 * Upon discovery of modified code in the process of support, the Licensee
 * is still held accountable for any and all billable time Sweet Tooth spent
 * during the support process.
 * Sweet Tooth does not guarantee compatibility with any other framework extension.
 * Sweet Tooth is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy
 * immediately.
 *
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * After order save observer. This class handles much of the catalog and cart points transfers
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Observer_Sales_Order_Save_After_Create
{
    /**
     * Creates the catalog and cart points transfers needed as a result of an order.
     *
     * @param   Varien_Event_Observer $observer
     * @return  TBT_Rewards_Model_Observer_Sales_Order_Save_After_Create
     * @event sales_order_place_after
     * @throws Mage_Core_Exception
     */
    public function createPointsTransfers($observer)
    {
        $event        = $observer->getEvent ();
        $order        = $event->getOrder ();
        $skipDispatch = false;

        // this event is fired twice, make sure we run code only once
        if ($event->getName() == 'sales_order_place_after' && Mage::registry('rewards_createPointsTransfers_run')) {
            return $this;
        }

        // Check if the method is triggered from
        // Mage::dispatchEvent('sales_order_payment_place_start', array('payment' => $this));
        if ( $event->getPayment() instanceof Mage_Sales_Model_Order_Payment ) {
            // skip this if we are in admin, because we do this on 'sales_order_place_after' event (only needed
            // for Authorize.net on front-end)
            if ($this->_isAdmin()) {
                return $this;
            }
            $skipDispatch = true;
            $order = $event->getPayment()->getOrder();
        }


        if (! $order) {
            return $this;
        }

        if (!$skipDispatch) {
            Mage::dispatchEvent( 'rewards_order_points_transfer_before_create', array (
                    'order' => $order,
                    'cart_transfers' => $this->_getCartTransfers()
            ));
        }

        try {
            Mage::helper('rewards/transfer')->validateCustomerBalance($order);
        } catch (Exception $ex) {
            throw new Mage_Core_Exception($ex->getMessage());
        }
        
        $this->_processCartTransfers($order);

        if (!$skipDispatch && !Mage::registry('rewards_createPointsTransfers_run')) {
            $this->_processAdditionalSpendingTransfers($order);
        }

        if (  $this->_doDisplayPendingMsgs($order)  ) {
            $this->_processPendingMsgs($order);
        }

        if (!$skipDispatch) {
            Mage::dispatchEvent( 'rewards_order_points_transfer_after_create', array (
                'order' => $order,
                'cart_transfers' => $this->_getCartTransfers()
            ));
        }

        if ($event->getName() == 'sales_order_place_after') {
            Mage::register('rewards_createPointsTransfers_run', true);
        }

        $this->_cleanUpTempData($order);

        return $this;
    }
    
    /**
     * Cleans up temporary data, cart points usage data and memory notes to process the points.
     */
    protected function _cleanUpTempData($order)
    {
        $cart_transfers = $this->_getCartTransfers();

        // Clear memory of points transfers and order
        $cart_transfers->clearIncrementId ();
        $cart_transfers->clearCartPoints ();

        return $this;
    }

    protected function _processAdditionalSpendingTransfers($order)
    {
        $pointsObj = new Varien_Object(array('points' => array()));

        Mage::dispatchEvent('rewards_tally_points_spent_on_cart_additional', array('points_obj' => $pointsObj, 'order' => $order, 'items' => $order->getAllItems()));

        $pointsAmountAdditional = $pointsObj->getPoints();

        if (empty($pointsAmountAdditional)) {
            return $this;
        }

        $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
        
        try {
            $isTransferSuccessful = Mage::helper('rewards/transfer')
                ->transferOrderPoints($pointsAmountAdditional[$currencyId], $order, null);
        } catch (Exception $exc) {
            throw new Mage_Core_Exception($exc->getMessage());
        }

        return $this;
    }

    /**
     * Process shopping cart rules
     * @param TBT_Rewards_Model_Sales_Order $order
     * @throws Mage_Core_Exception
     */
    protected function _processCartTransfers($order)
    {
        $cart_transfers = Mage::getSingleton ( 'rewards/observer_sales_carttransfers' );

        if ($order->getIncrementId () != $cart_transfers->getIncrementId ()) {
            return $this;
        }

        $this->_processAllCartPoints($cart_transfers, $order);

        $this->_processCartRedemptionRules($cart_transfers, $order);

        return $this;
    }

    /**
     *
     * @param TBT_Rewards_Model_Observer_Sales_Carttransfers $cart_transfers
     * @param TBT_Rewards_Model_Sales_Order $order
     * @throws Mage_Core_Exception
     */
    protected function _processAllCartPoints($cart_transfers, $order)
    {
        foreach ( $cart_transfers->getAllCartPoints () as $cart_points ) {
            if (! $cart_points) {
                continue;
            }

            try {
                $is_transfer_successful = Mage::helper ( 'rewards/transfer' )->transferOrderPoints ( $cart_points ['amount'], $order, $cart_points ['rule_id'] );
            } catch ( Exception $ex ) {
                throw new Mage_Core_Exception ( $ex->getMessage () );
            }
        }

        return $this;
    }

    /**
     *
     * @param TBT_Rewards_Model_Observer_Sales_Carttransfers $cart_transfers
     * @param TBT_Rewards_Model_Sales_Order $order
     * @throws Mage_Core_Exception
     */
    protected function _processCartRedemptionRules($cart_transfers, $order)
    {
        foreach ( $cart_transfers->getRedemptionRuleIds () as $rule_id ) {
            $points = null;

            // Get the points amount
            try {
                $points = $this->_getRewardsSession ()->calculateCartPoints ( $rule_id, $order->getAllItems (), true );
            } catch ( Exception $e ) {
                Mage::logException($e);
                throw new Mage_Core_Exception($e->getMessage());
            }

            // If no point samount was retrieved, continue on to the next redemption
            if (!is_array ( $points )) {
                continue;
            }

            // Try to transfer points to the customer
            try {
                $is_transfer_successful = Mage::helper ( 'rewards/transfer' )->transferOrderPoints ( $points ['amount'], $order, $rule_id );
            } catch ( Exception $ex ) {
                throw new Mage_Core_Exception ( $ex->getMessage () );
            }
        }

        return $this;

    }


    /**
     * If there exist any points
     * @param TBT_Rewards_Model_Sales_Order $order
     * @return boolean
     */
    protected function _doDisplayPendingMsgs($order)
    {
        $cart_transfers = Mage::getSingleton ( 'rewards/observer_sales_carttransfers' );

        // If cart transfers dont match the order increment ID or no increment ID is in the current order model, don't display
        // any messages because we'll probably make a second pass through this function.
        // @nelkaake: I have no idea why we only check the cart transfers here,
        //             but we seem to not be setting the catalog_transfers variable correctly above...
        if ($order->getIncrementId () != $cart_transfers->getIncrementId ()) {
            return false;
        }

        // If no point swere earned or spent, there is no need to display any messages.
        if (!$order->hasPointsEarningOrSpending()) {
            return false;
        }

        // TODO @nelkaake 08-18-2011: is this next IF statement needed?  I know the process pending msgs is, but do we need the if statement?
        if(!$order->isInitialTransferStatus(TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT)) {
            return false;
        }

        return true;
    }

    /**
     * Sends any order and pending messages to the display
     * @param TBT_Rewards_Model_Sales_Order $order
     */
    protected function _processPendingMsgs($order)
    {
        $earned_points_string = Mage::getModel('rewards/points')->set($order->getTotalEarnedPoints());
        $redeemed_points_string = Mage::getModel('rewards/points')->set($order->getTotalSpentPoints());

        if ($this->_getRewardsSession ()->isAdminMode ()) {
            return $this->_processAdminPendingMsgs($order);
        }

        if ($order->hasPointsEarning () && !$order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'You earned %s for the order you just placed.', $earned_points_string ) );
        } elseif (!$order->hasPointsEarning () && $order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'You spent %s for the order you just placed.', $redeemed_points_string ) );
        } elseif ($order->hasPointsEarning () && $order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'You earned %s and spent %s for the order you just placed.', $earned_points_string, $redeemed_points_string ) );
        } else {
            // no points earned or spent
        }

        if ($order->isInitialTransferStatus(TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT)) {
            $this->_dispatchSuccess (Mage::helper('rewards')->__('The points you earned are currently pending the completion of your order. You will be able to spend these points after we finish processing your order.'));
        } elseif ($order->isInitialTransferStatus(TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_APPROVAL)) {
            $this->_dispatchSuccess (Mage::helper('rewards')->__('The points you earned are currently on hold until we have processed your order. You will be able to spend these points after an administrator has approved the order.'));
        } else {
            // points were likely approved, so no extra message is needed.
        }

        return $this;
    }



    /**
     * Sends any order and pending messages to the display
     * @param TBT_Rewards_Model_Sales_Order $order
     */
    protected function _processAdminPendingMsgs($order)
    {
        $earned_points_string = Mage::getModel('rewards/points')->set($order->getTotalEarnedPoints());
        $redeemed_points_string = Mage::getModel('rewards/points')->set($order->getTotalSpentPoints());

        if(!$this->_getRewardsSession ()->isAdminMode ()) {
            return $this;
        }

        if ($order->hasPointsEarning () && !$order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'The customer was credited %s for the order.', $earned_points_string ) );
        } elseif (!$order->hasPointsEarning () && $order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'The customer was deducted %s for the order.', $redeemed_points_string ) );
        } elseif ($order->hasPointsEarning () && $order->hasPointsSpending ()) {
            $this->_dispatchSuccess ( Mage::helper ( 'rewards' )->__ ( 'The customer was credited %s and deducted %s for the order.', $earned_points_string, $redeemed_points_string ) );
        } else {
            // no points earned or spent
        }

        if ($order->isInitialTransferStatus(TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT)) {
            $this->_dispatchSuccess (Mage::helper('rewards')->__("The customer's earned points are currently pending the completion of the order. They will be able to spend their points after order process is complete."));
        } elseif ($order->isInitialTransferStatus(TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_APPROVAL)) {
            $this->_dispatchSuccess (Mage::helper('rewards')->__("The customer's earned points are currently on hold. The customer will not be able to spend their points until an administator approves the transaction(s)."));
        } else {
            // points were likely approved, so no extra message is needed.
        }


        return $this;
    }


    /**
     * @param Mage_Core_Model_Message $message
     */
    protected function _dispatchCheckoutMsg($message)
    {
        //Mage::helper('rewards/debug')->noticeBacktrace("Adding msg with _dispatchCheckoutMsg: ". $message->getText());
        $message->setIdentifier('TBT_Rewards_Model_Observer_Sales_Order_Save_After_Create(pending points)');
        
        if (
            Mage::app()->getStore()->isCurrentlySecure()
            && Mage::helper('rewards/version')->isBaseMageVersionAtLeast('1.9.1.0')
        ) {
            Mage::getSingleton('core/session')->addMessage($message);
        } else {
            Mage::getSingleton('checkout/session')->addMessage($message);
        }

        return $this;
    }


    /**
     * @param string $str_msg
     */
    protected function _dispatchSuccess($str_msg)
    {
        /* @var $message Mage_Core_Model_Message */
        $message_factory = Mage::getSingleton('core/message');
        $message = $message_factory->success($str_msg);

        return $this->_dispatchCheckoutMsg($message);
    }
    /**
     * @param string $string
     */
    protected function _dispatchNotice($str_msg)
    {
        /* @var $message Mage_Core_Model_Message */
        $message_factory = Mage::getSingleton('core/message');
        $message = $message_factory->notice($str_msg);

        return $this->_dispatchCheckoutMsg($message);
    }

    /**
     * Fetches the rewards session
     *
     * @return TBT_Rewards_Model_Session
     */
    protected function _getRewardsSession()
    {
        return Mage::getSingleton ( 'rewards/session' );
    }

    /**
     * @return TBT_Rewards_Observer_Sales_Carttransfers
     */
    protected function _getCartTransfers()
    {
        return Mage::getSingleton ( 'rewards/observer_sales_carttransfers' );
    }

    /**
     * Checks if we are in Admin (back-end) section.
     * @return boolean Returns true if we are in admin, false otherwise
     */
    protected function  _isAdmin()
    {
        return Mage::helper('rewards')->getIsAdmin();
    }

}
