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
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class TBT_Rewards_Model_Tag_Transfer extends TBT_Rewards_Model_Transfer 
{
    public function __construct() 
    {
        parent::__construct ();
    }

    public function setTagId($id) 
    {
        $this->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('tag'));
        $this->setReferenceId ( $id );

        return $this;
    }

    public function isTag() 
    {
        return ($this->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('tag'));
    }

    public function getTransfersAssociatedWithTag($tag_id) 
    {
        return $this->getCollection()
            ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('tag'))
            ->addFieldToFilter('reference_id', $tag_id);
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
    protected function _getTagValidator() 
    {
        return Mage::getSingleton ( 'rewards/tag_validator' );
    }

    /**
     * Do the points transfer for the tag
     *
     * @param  TBT_Rewards_Model_Tag_Wrapper $tag
     * @param  int $rule       : Special Rule
     * @return boolean            : whether or not the point-transfer succeeded
     */
    public function transferTagPoints($tag, $rule) 
    {
        $num_points = $rule->getPointsAmount ();
        $tag_id = $tag->getId ();
        $rule_id = $rule->getId ();
        $transfer = $this->initTransfer ( $num_points, $rule_id );

        if (! $transfer) {
            return false;
        }

        // get the default starting status - usually Pending
        if (! $transfer->setStatusId ( null, Mage::helper ( 'rewards/config' )->getInitialTransferStatusAfterTag () )) {
            return false;
        }

        $customerId = Mage::getModel ( 'tag/tag' )->load ( $tag_id )->getFirstCustomerId ();

        // Check for daily and weekly review limits
        $skipDates = Mage::getSingleton('rewards/session')->getSkipDates();
        if (!$skipDates && !$this->isWithinLimits($customerId)) {
            return false;
        }

        $transfer->setTagId($tag_id)
            ->setCustomerId($customerId)
            ->setReferenceId($tag_id)
            ->setReasonId(Mage::helper('rewards/transfer_reason')->getReasonId('tag'))
            ->setComments(Mage::getStoreConfig('rewards/transferComments/tagEarned'))
            ->save();

        return true;
    }

    /**
     * Check if the daily & weekly tag limits for point earnings were reached 
     * 
     * @param  int $customerId
     * @return bool 
     */
    public function isWithinLimits($customerId) 
    {
        $isWithinLimits = true;
        
        if (!$this->isWithinDailyLimit($customerId)) {
            $isWithinLimits = false;
            Mage::getSingleton('core/session')->addError(
                Mage::helper('rewards')->__("You've reached the tags rewards limit for today. You will not receive any points for this tag.")
            );
        } elseif (!$this->isWithinWeeklyLimit($customerId)) {
            $isWithinLimits = false;
            Mage::getSingleton('core/session')->addError(
                Mage::helper('rewards')->__("You've reached the tags rewards limit for this week. You will not receive any points for this tag.")
            );
        }
        
        return $isWithinLimits;
    }
    
    /**
     * Check if the daily review limits for point earnings were reached 
     * 
     * @param  int $customerId
     * @return bool 
     */
    public function isWithinDailyLimit($customerId)
    {
        // Get system config settings
        $dailyTagLimit = Mage::getStoreConfig('rewards/reviews_and_tags/daily_tag_limit');
        
        // Calculate seconds in a day and in a week
        $secondsInADay = 60 * 60 * 24;
        $yesterday = time() - $secondsInADay;
        
        //Get tag ids (pending and approved) posted by the customer since yesterday
        $tagIds = Mage::getModel('tag/tag')
            ->getResourceCollection()
            ->joinRel()
            ->addFieldToFilter('relation.store_id', Mage::app()->getStore()->getId())
            ->addCustomerFilter($customerId)
            ->addFieldToFilter('status', array(Mage_Tag_Model_Tag::STATUS_APPROVED, Mage_Tag_Model_Tag::STATUS_PENDING))
            ->addFieldToFilter('created_at', array('gteq' => $yesterday))
            ->getAllIds();
        
        // Count Tags that have a transfer associated to them
        $numberOfTagsInThePastDay = Mage::getModel('rewards/transfer')
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('tag'))
            ->addFieldToFilter('reference_id', array('in' => $tagIds))
            ->getSize();
        
        // Count current tag as well (which doesn't have a transfer yet)
        $numberOfTagsInThePastDay++;
        
        return !($dailyTagLimit > 0 && $numberOfTagsInThePastDay > $dailyTagLimit);
    }
    
    /**
     * Check if the weekly review limits for point earnings were reached 
     * 
     * @param  int $customerId
     * @return bool 
     */
    public function isWithinWeeklyLimit($customerId)
    {
        // Get system config settings
        $weeklyTagLimit = Mage::getStoreConfig('rewards/reviews_and_tags/weekly_tag_limit');
        
        // Calculate seconds in a day and in a week
        $secondsInADay = 60 * 60 * 24;
        $oneWeekAgo = time() - 7 * $secondsInADay;
        
        //Get tag ids (pending and approved) posted by the customer between now and a week ago
        $tagIds = Mage::getModel('tag/tag')
            ->getResourceCollection()
            ->joinRel()
            ->addFieldToFilter('relation.store_id', Mage::app()->getStore()->getId())
            ->addCustomerFilter($customerId)
            ->addFieldToFilter('status', array(Mage_Tag_Model_Tag::STATUS_APPROVED, Mage_Tag_Model_Tag::STATUS_PENDING))
            ->addFieldToFilter('created_at', array('gteq' => $oneWeekAgo))
            ->getAllIds();
        
        // Count Tags that have a transfer associated to them
        $numberOfTagsInThePastWeek = Mage::getModel('rewards/transfer')
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('tag'))
            ->addFieldToFilter('reference_id', array('in' => $tagIds))
            ->getSize();
        
        // Count current tag as well (which doesn't have a transfer yet)
        $numberOfTagsInThePastWeek++;
        
        return !($weeklyTagLimit > 0 && $numberOfTagsInThePastWeek > $weeklyTagLimit);
    }
}
