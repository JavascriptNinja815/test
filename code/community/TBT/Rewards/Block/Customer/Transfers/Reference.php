<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the SWEET TOOTH POINTS AND REWARDS
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
 */

/**
 * Customer Transfers Reference Block
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Customer_Transfers_Reference extends Mage_Core_Block_Template 
{
    protected function _construct() 
    {
        parent::_construct ();
        $this->_controller = 'customer';
        $this->_blockGroup = 'rewards';
        $this->setTemplate ( 'rewards/customer/transfers/reference.phtml' );
    }

    /**
     * Returns a rewards customer
     * @return TBT_Rewards_Model_Customer
     */
    public function getCustomer() 
    {
        $customer = Mage::registry ( 'customer' );
        return Mage::getModel('rewards/customer')->getRewardsCustomer($customer);
    }

    public function _getTransferSummary() 
    {
        $cust = $this->getCustomer ();
        $transfers = $cust->getTransfers ()->setOrder ( 'created_at', 'DESC' );
        return $transfers;
    }

    /**
     * Fetches an order ID from a given transfer id
     * @see TBT_Rewards_Model_Transfer
     *
     * @param int $transferId
     * @return int | null
     */
    public function getAssociatedOrderId($transferId) 
    {
        $transfer = Mage::getModel('rewards/transfer')->load($transferId);

        if (
            $transfer 
            && $transfer->getId() 
            && $transfer->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('order')
        ) {
            return $transfer->getReferenceId();
        }
        
        return null;
    }

    /**
     * DEPRECATED
     * @use getAssociatedOrderId()
     */
    public function getAssociatedOrder($transferId)
    {
        return $this->getAssociatedOrderId($transferId);
    }

    /**
     * Fetches a review ID from a given transfer id
     * @see TBT_Rewards_Model_Transfer
     *
     * @param int $transferId
     * @return int | null
     */
    public function getAssociatedReviewId($transferId) 
    {
        $transfer = Mage::getModel('rewards/transfer')->load($transferId);

        if (
            $transfer 
            && $transfer->getId() 
            && $transfer->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('product_review')
        ) {
            return $transfer->getReferenceId();
        }
        
        return null;
    }

    /**
     * DEPRECATED
     * @use getAssociatedReviewId()
     */
    public function getAssociatedReview($transferId) 
    {
        return $this->getAssociatedReviewId($transferId);
    }

    /**
     * DEPRECATED
     * @use getAssociatedReviewId()
     */
    public function getAssociatedRating($transferId) 
    {
        return $this->getAssociatedReviewId($transferId);
    }

    /**
     * Fetches a poll ID from a given transfer id
     * @see TBT_Rewards_Model_Transfer
     *
     * @param int $transferId
     * @return int | null
     */
    public function getAssociatedPollId($transferId) 
    {
        $transfer = Mage::getModel('rewards/transfer')->load($transferId);

        if (
            $transfer 
            && $transfer->getId() 
            && $transfer->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('poll')
        ) {
            return $transfer->getReferenceId();
        }
        
        return null;
    }

    /**
     * DEPRECATED
     * @use getAssociatedPollId()
     */
    public function getAssociatedPoll($transferId)
    {
        return $this->getAssociatedPollId($transferId);
    }

    /**
     * Fetches an order ID from a given transfer id
     * @see TBT_Rewards_Model_Transfer
     *
     * @param int $transferId
     * @return int | null
     */
    public function getAssociatedTagId($transferId) 
    {
        $transfer = Mage::getModel('rewards/transfer')->load($transferId);

        if (
            $transfer 
            && $transfer->getId() 
            && $transfer->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('tag')
        ) {
            return $transfer->getReferenceId();
        }
        
        return null;
    }

    /**
     * DEPRECATED
     * @use getAssociatedTagId()
     */
    public function getAssociatedTag($transferId) 
    {
        return $this->getAssociatedTagId($transferId);
    }

    /**
     * Fetch Status Caption
     * 
     * @param int $status_id
     * @return string
     */
    public function getStatusCaption($status_id)
    {
        return Mage::getModel ( 'rewards/transfer_status' )->getStatusCaption ( $status_id );
    }

    /**
     * Fetch Points String (ie: 100 Gold Points)
     * 
     * @param int $amount
     * @param int $currency
     * @return string
     */
    public function getPointsString($amount, $currency)
    {
        $str = Mage::helper ( 'rewards' )->getPointsString ( array ($currency => $amount ) );
        return $str;
    }

    /**
     * Fetch customer email
     * 
     * @param int $customer_id
     * @return string
     */
    public function getCustomerEmail($customer_id)
    {
        return Mage::getModel ( 'rewards/customer' )->load ( $customer_id )->getEmail ();
    }

    /**
     * Fetch order id
     * 
     * @param int $order_id
     * @return string
     */
    public function getOrderUrl($order_id)
    {
        return $this->getUrl ( 'sales/order/view', array ('order_id' => $order_id ) );
    }

    /**
     * Fetch Review Url
     * 
     * @param int $review_id
     * @return string
     */
    public function getReviewUrl($review_id)
    {
        return $this->getUrl ( 'review/customer/view', array ('id' => $review_id ) );
    }
}
