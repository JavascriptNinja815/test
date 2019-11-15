<?php

/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL: 
 *      https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL: 
 *      http://opensource.org/licenses/osl-3.0.php
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
class TBT_RewardsReferral_Model_Transfer extends TBT_Rewards_Model_Transfer 
{
    /**
     * Get transfers associated to a certain customer
     * 
     * @param int $friend_id
     * @return TBT_RewardsReferral_Model_Mysql4_Transfer_Collection
     */
    public function getTransfersAssociatedWithReferredFriend($friend_id) 
    {
        return Mage::getResourceModel('rewardsref/transfer_collection')
            ->filterReferralTransfers($friend_id);
    }

    /**
     *
     * @param type $num_points
     * @param type $currency_id
     * @param type $earnerCustomerId
     * @param type $referredCustomerId
     * @param type $comment
     * @param type $reason_id
     * @param type $transferStatus TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED
     * @param type $referenceOrderId link transfer to order
     * @return TBT_RewardsReferral_Model_Transfer
     */
    public function create($num_points, $earnerCustomerId, $referredCustomerId, $comment = "", 
            $reason_id, 
            $transferStatus = TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED, 
            $referenceOrderId = null,
            $transferEffectiveStart = null
    ) {
        
        if (!$reason_id) {
            $reason_id = Mage::helper('rewards/transfer_reason')->getReasonId('order');
        }
        
     // ALWAYS ensure that we only give an integral amount of points
        $num_points = floor( $num_points );
        if ( $num_points <= 0 ) {
            return $this;
        }
        
        if (!is_null($transferEffectiveStart)) {
            $this->setEffectiveStart($transferEffectiveStart);
        }
        
        $referenceId = (empty($referenceOrderId)) ? $referredCustomerId : $referenceOrderId;
        $this->setReasonId($reason_id)
            ->setReferenceId($referenceId);
        
        if ( ! $this->setStatusId( null, $transferStatus ) ) {
            return $this;
        }
        
        $now = Mage::getModel('core/date')->gmtDate();
        $this->setId( null )
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setQuantity( $num_points )
            ->setComments( $comment )
            ->setCustomerId( $earnerCustomerId )
            ->save();
        
        return $this;
    }
}
