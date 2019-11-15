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
 * Tag Wrapper
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Tag_Wrapper extends Varien_Object 
{
    /**
     * @var Mage_Tag_Model_Tag
     */
    protected $_tag;

    /**
     * @param Mage_Tag_Model_Tag $subscriber
     */
    public function wrap(Mage_Tag_Model_Tag $tag) 
    {
        $this->_tag = $tag;
        return $this;
    }

    /**
     * Return the wrapped tag
     * @return Mage_Tag_Model_Tag
     */
    public function getTag() 
    {
        return $this->_tag;
    }

    /**
     * Returns true if it's Pending!
     * @return boolean
     */
    public function isPending() 
    {
        return $this->getTag ()->getStatusId () == Mage_Tag_Model_Tag::STATUS_PENDING;
    }

    /**
     * Returns true if it's Approved!
     * @return boolean
     */
    public function isApproved() 
    {
        return $this->getTag ()->getStatusId () == Mage_Tag_Model_Tag::STATUS_APPROVED;
    }

    /**
     * Returns true if it's not Approved!
     * @return boolean
     */
    public function isNotApproved() 
    {
        return $this->getTag ()->getStatusId () == Mage_Tag_Model_Tag::STATUS_NOT_APPROVED;
    }

    /**
     * Approves all associated transfers with a pending status.
     */
    public function approvePendingTransfers() 
    {
        foreach ( $this->getAssociatedTransfers () as $transfer ) {
            if ($transfer->getStatusId () == TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT) {
                //Move the transfer status from pending to approved, and save it!
                $transfer->setStatusId ( TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT, TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED );
                $transfer->save ();
            }
        }
    }

    /**
     * Discards all associated transfers with a pending status.
     */
    public function discardPendingTransfers() 
    {
        foreach ( $this->getAssociatedTransfers () as $transfer ) {
            if ($transfer->getStatusId () == TBT_Rewards_Model_Transfer_Status::STATUS_PENDING) {
                //Move the transfer status from pending to approved, and save it!
                $transfer->setStatusId ( TBT_Rewards_Model_Transfer_Status::STATUS_PENDING, TBT_Rewards_Model_Transfer_Status::STATUS_CANCELLED );
                $transfer->save ();
            }

            $this->createTransferForNextTag($transfer->getCustomerId());
        }
    }

    /**
     * Loops through each Special rule. If it applies, create a new pending transfer.
     */
    public function ifNewTag() 
    {
        $ruleCollection = Mage::getSingleton('rewards/tag_validator')->getApplicableRulesOnTag();
        foreach ($ruleCollection as $rule) {
            $isTransferSuccessful = $this->createPendingTransfer($rule);
            if ($isTransferSuccessful) {
                $initialTransferStatusForTag = Mage::helper('rewards/config')->getInitialTransferStatusAfterTag();
                $message = ($initialTransferStatusForTag == 5) 
                    ? 'You received %s for this tag'
                    : 'You will receive %s upon approval of this tag';
                
                Mage::getSingleton('core/session')->addSuccess(
                    Mage::helper('rewards')->__(
                        $message, 
                        (string) Mage::getModel('rewards/points')->set($rule)
                    )
                );
            }
        }
    }

    /**
     * Creates a transfer for the next tag that matches each rule
     * 
     * @param int $customerId
     */
    public function createTransferForNextTag($customerId)
    {
        // We get the last transfer, so the newly created transfers will not be included in our select
        $lastTransferId = Mage::getModel('rewards/transfer')
            ->getCollection()
            ->setOrder('rewards_transfer_id','DESC')
            ->setPageSize(1)
            ->getFirstItem()
            ->getId();

        // Get applicable rules without filtering for current date as we process past data
        Mage::getSingleton('rewards/session')->setSkipDates(true);
        $ruleCollection = Mage::getSingleton('rewards/tag_validator')->getApplicableRulesOnTag();

        foreach ($ruleCollection as $rule) {
            $fromDate = $rule->getFromDate();
            $endDate = $rule->getToDate();

            // Get all tag ID's on which the customer has transfers
            $transfers = Mage::getModel('rewards/transfer')
                ->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                // the below table contains the tag IDs we are looking for
                ->addFieldToFilter('rewards_transfer_id', array('lteq' => $lastTransferId))
                // we only care about tag related transfers
                ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('tag'));
            $transfers->getSelect()
                // we remove all columns from the select as we only need the tag IDs, no need to fetch anything else
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns('reference_id');

            $includedTagIds = array();
            foreach ($transfers as $transfer) {
                $includedTagIds[] = $transfer->getReferenceId();
            }

            $nextTag = Mage::getModel('tag/tag')->getResourceCollection()
                ->joinRel();

            if (!empty($fromDate)) {
                $nextTag->addFieldToFilter('created_at', array('gteq' => $fromDate));
            }

            if (!empty($endDate)) {
                $nextTag->addFieldToFilter('created_at', array('lteq' => $endDate));
            }

            // Fetch next valid tag
            $nextTag->addCustomerFilter($customerId)
                ->addFieldToFilter('status', array(Mage_Tag_Model_Tag::STATUS_APPROVED, Mage_Tag_Model_Tag::STATUS_PENDING))
                ->addFieldToFilter('main_table.tag_id', array('nin' => $includedTagIds))
                ->setOrder('main_table.tag_id','ASC')
                ->setPageSize(1);

            if ($nextTag->getSize() > 0) {
                $this->createPendingTransfer($rule, $nextTag->getFirstItem());
            } 

            Mage::getSingleton('rewards/session')->setSkipDates(false);
        }
    }

    /**
     * Returns a collection of all transfers associated with this tag
     *
     * @return array(TBT_Rewards_Model_Transfer) : A collection of all tags associated with this tag
     */
    public function getAssociatedTransfers() 
    {
        return Mage::getModel ( 'rewards/tag_transfer' )->getTransfersAssociatedWithTag ( $this->getTag ()->getId () );
    }

    /**
     * Creates a new transfer with a pending status using the rule information
     *
     * @param TBT_Rewards_Model_Special $rule
     */
    public function createPendingTransfer($rule, $tag = null) 
    {
        if (empty($tag)) {
            $tag = $this->getTag();
        }
        try {
            $is_transfer_successful = Mage::getModel ( 'rewards/tag_transfer' )->transferTagPoints($tag, $rule);
        } catch ( Exception $ex ) {
            Mage::helper('rewards/debug')->log($ex->getMessage());
            Mage::getSingleton ( 'core/session' )->addError ( $ex->getMessage () );
        }
        
        return $is_transfer_successful;
    }
}
