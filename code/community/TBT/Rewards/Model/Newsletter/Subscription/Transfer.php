<?php

/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).

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
 * Transfer
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Newsletter_Subscription_Transfer extends TBT_Rewards_Model_Transfer 
{
    /**
     * @param int $id
     */
    public function setNewsletterId($id) 
    {
        $this->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('newsletter'));
        $this->setReferenceId ( $id );

        return $this;
    }

    /**
     * @return boolean
     */
    public function isNewsletter() 
    {
        return ($this->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('newsletter'));
    }

    /**
     * 
     * Gets all transfers associated with the given newsletter ID
     * @param int $newsletter_id
     */
    public function getTransfersAssociatedWithNewsletter($newsletter_id) 
    {
        return $this->getCollection()
            ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('newsletter'))
            ->addFieldToFilter('reference_id', $newsletter_id);
    }

    /**
     * Fetches the transfer helper
     *
     * @return TBT_Rewards_Helper_Transfer
     */
    protected function _getTransferHelper() 
    {
        return Mage::helper ( 'rewards/transfer' );
    }

    /**
     * Fetches the rewards special validator singleton
     *
     * @return TBT_Rewards_Model_Special_Validator
     */
    protected function _getSpecialValidator() 
    {
        return Mage::getSingleton ( 'rewards/special_validator' );
    }

    /**
     * Fetches the rewards special validator singleton
     *
     * @return TBT_Rewards_Model_Special_Validator
     */
    protected function _getNewsletterValidator() 
    {
        return Mage::getSingleton ( 'rewards/newsletter_validator' );
    }

    /**
     * Creates a customer point-transfer of any amount or currency.
     *
     * @param  $rule    : Special Rule
     * @return boolean            : whether or not the point-transfer succeeded
     */
    public function createNewsletterSubscriptionPoints(TBT_Rewards_Model_Newsletter_Subscriber_Wrapper $rsubscriber, $rule)
    {
        $num_points = $rule->getPointsAmount ();
        $rule_id = $rule->getId ();
        $customer = Mage::getModel('rewards/customer')->getRewardsCustomer($rsubscriber->getCustomer ());
        $store_id = $customer->getStore ()->getId ();
        $customer_id = $rsubscriber->getCustomer ()->getId ();
        $transfer = $this->initTransfer($num_points, $rule_id, $customer_id, true);


        if (!$transfer || !$customer_id) {
            return false;
        }

        $transfer->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('newsletter'));

        /* get the default starting status - usually Pending */
        $initial_status = Mage::helper ( 'rewards/newsletter_config' )->getInitialTransferStatusAfterNewsletter ( $store_id );

        if (! $transfer->setStatusId ( null, $initial_status )) {
            /* we tried to use an invalid status... is getInitialTransferStatusAfterReview() improper ?? */
            return false;
        }

        /* 
         * Translate the message through the core translation engine (nto the store view system) in case people want to use that instead
         * This is not normal, but we found that a lot of people preferred to use the standard translation system insteaed of the
         * store view system so this lets them use both.
         */
        $initial_transfer_msg = Mage::getStoreConfig ( 'rewards/transferComments/newsletterEarned', $store_id );
        $comments = Mage::helper ( 'rewards' )->__ ( $initial_transfer_msg );

        $this->setNewsletterId($customer_id)
            ->setComments($comments)
            ->setCustomerId($customer_id)
            ->save();

        return true;
    }
}

