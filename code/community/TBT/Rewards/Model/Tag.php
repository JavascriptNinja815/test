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
 * Tag
 * 
 * @deprecated this used to be a rewrite but now Sweet Tooth uses an Observer for the Tag model
 * @see TBT_Rewards_Model_Tag_Observer
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Tag extends Mage_Tag_Model_Tag {
	
	protected $oldData = null; //This is used to store data from the model to compare to future versions
	

	/**
	 * Processing object before save data
	 *
	 * @return Mage_Core_Model_Abstract
	 */
	
	protected function _afterLoad() {
		//Before you save, pass all current data into a dummy model for comparison later. 
		$this->oldData = $this->getData ();
		return parent::_afterLoad ();
	}
	
	/**
	 * Processing object after save data
	 *
	 * @return Mage_Core_Model_Abstract
	 */
	protected function _afterSave() {
		//If the tag becomes approved, approve all associated pending tranfser
		if ($this->oldData ['status'] == self::STATUS_PENDING && $this->getStatus () == self::STATUS_APPROVED) {
			$this->approvePendingTransfers ();
		
		//If the review is new (hence not having an id before) get applicable rules, 
		//and create a pending transfer for each one
		} elseif ($this->getTagId () && ! isset ( $this->oldData ['tag_id'] )) {
			Mage::dispatchEvent ( 'rewards_new_tag', array ('tag' => &$this ) );
			$this->onNewTag ();
		}
		return parent::_afterSave ();
	}
	
	/**
	 * Approves all associated transfers with a pending status.
	 */
	protected function approvePendingTransfers() {
		foreach ( $this->getAssociatedTransfers () as $transfer ) {
			if ($transfer->getStatusId () == TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT) {
				//Move the transfer status from pending to approved, and save it!
				$transfer->setStatusId ( TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT, TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED );
				$transfer->save ();
			}
		}
	}
	
	/**
	 * Loops through each Special rule. If it applies, create a new pending transfer.
	 */
	protected function onNewTag() 
        {
            $ruleCollection = Mage::getSingleton('rewards/special_validator')->getApplicableRulesOnTag();
            foreach ($ruleCollection as $rule) {
                $isTransferSuccessful = $this->createPendingTransfer($rule);

                if ($isTransferSuccessful) {
                    $initialTransferStatusForTag = Mage::helper('rewards/config')->getInitialTransferStatusAfterTag();
                    $message = ($initialTransferStatusForTag == 5) 
                        ? 'You received %s for this tag'
                        : 'You will receive %s upon approval of this tag';

                    $pts = Mage::getModel('rewards/points')->setPoints($rule);
                    $msg = Mage::helper('rewards')->__($message, $pts);
                    Mage::getSingleton('core/session')->addSuccess($msg);
                }
            }
	}
	
	/**
	 * Creates a new transfer with a pending status using the rule information
	 *
	 * @param TBT_Rewards_Model_Special $rule
	 */
	protected function createPendingTransfer($rule) {
		try {
			$is_transfer_successful = Mage::getModel('rewards/tag_transfer')->transferTagPoints ( $rule->getPointsAmount (), $this->getId (), Mage::getSingleton ( 'customer/session' )->getCustomerId (), $rule->getId () );
		} catch ( Exception $ex ) {
			Mage::getSingleton ( 'core/session' )->addError ( $ex->getMessage () );
		}
		
		return $is_transfer_successful;
	}
	
	/**
	 * Returns an array outlining the number of points they will receive for tagging a product
	 *
	 * @return array
	 */
	public function getPredictPoints() {
		Varien_Profiler::start ( "TBT_Rewards:: Predict Tag Points" );
		$ruleCollection = Mage::getSingleton ( 'rewards/special_validator' )->getApplicableRulesOnTag ();
		$predict_array = array ();
		foreach ( $ruleCollection as $rule ) {
			$predict_array [$rule->getPointsCurrencyId ()] = $rule->getPointsAmount ();
		}
		
		Varien_Profiler::stop ( "TBT_Rewards:: Predict Tag Points" );
		return $predict_array;
	}
	
	/**
	 * Returns a collection of all transfers associated with this tag
	 *
	 * @return array(TBT_Rewards_Model_Transfer)   
	 */
	protected function getAssociatedTransfers() {
		return Mage::getModel ( 'rewards/tag_transfer' )->getTransfersAssociatedWithTag ( $this->getId () );
	}

}

