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
 * This class is used as a template for order observer classes
 * @package     TBT_Rewards
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Observer_Sales_Order_Abstract
{
    /**
     * Approve point transfers for order
     * @param Mage_Sales_Model_Order $order
     */
    public function approveOrderPointTransfers($order)
    {
        if (!$order || !($order instanceof Mage_Sales_Model_Order) || !$order->getId()) {
            return $this;
        }
        
        $dispatchMsgs = false;
        $orderTransfers = Mage::getModel ( 'rewards/transfer' )->getTransfersAssociatedWithOrder ( $order->getId () );

        Mage::dispatchEvent('rewards_order_points_transfer_before_approved',
            array(
                'order'     => $order,
                'transfers' => $orderTransfers
            )
        );

        foreach ( $orderTransfers as $transfer ) {
            if ($transfer->getStatusId () == TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT) {
                $dispatchMsgs = true;
                $transfer->setStatusId ( $transfer->getStatusId(), TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED );
                $transfer->save ();
            }
        }

        /**
         * Notify customer
         */
        if ($dispatchMsgs) {
            $this->_dispatchTransferMsgs($order);
        }

        Mage::dispatchEvent('rewards_order_points_transfer_after_approved',
            array(
                'order'     => $order,
                'transfers' => $orderTransfers
            )
        );
    }


    /**
     * Sends any order and pending messages to the display
     *
     * @param TBT_Rewards_Model_Sales_Order $order
     */
    protected function _dispatchTransferMsgs($order)
    {
        $earnedPointsString   = Mage::getModel('rewards/points')->set($order->getTotalEarnedPoints());
        $redeemedPointsString = Mage::getModel('rewards/points')->set($order->getTotalSpentPoints());

        if ($order->hasPointsEarning()) {
            if ($this->_getRewardsSession()->isAdminMode()) {
                Mage::getSingleton('core/session')->addSuccess(
                    Mage::helper('rewards')->__('%s were approved for the order.', $earnedPointsString)
                );
            }
        }

        return $this;
    }

    /**
     * Fetches the rewards session
     *
     * @return TBT_Rewards_Model_Session
     */
    protected function _getRewardsSession()
    {
        return Mage::getSingleton('rewards/session');
    }
}