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
 * Transfer Reference
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Newsletter_Subscription_Reference extends TBT_Rewards_Model_Transfer_Reference_Abstract {
	const REFERENCE_TYPE_ID = 10;
	//protected $_transferCellRenderer = 'rewardsref/customer_transfers_referral_cell';
	/* 
     * in the format [reference_type] => renderer
    public function getTRefCellRenderers() {
        return array(self::REFERENCE_TYPE_ID => $this->_transferCellRenderer);
    }
    
    
    
    */
	
	public function clearReferences(&$transfer) {
		if ($transfer->hasData ( 'newsletter_id' )) {
			$transfer->unsetData ( 'newsletter_id' );
		}
		return $this;
	}
	
	public function getReferenceOptions() {
		$reference_options = array (self::REFERENCE_TYPE_ID => Mage::helper ( 'rewards' )->__ ( 'Newsletter' ) );
		return $reference_options;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see TBT_Rewards_Model_Transfer_Reference_Abstract::loadReferenceInformation()
	 */
	public function loadReferenceInformation(&$transfer) {
		$this->loadTransferId ( $transfer );
		return $this;
	}
	
	/**
	 * 
	 * @param TBT_Rewards_Model_Transfer $transfer
	 * @param int $id
	 */
	public function loadTransferId($transfer) {
		$id = $transfer->getReferenceId ();
		$transfer->setReferenceType ( TBT_Rewards_Model_Newsletter_Subscription_Reference::REFERENCE_TYPE_ID );
		$transfer->setReferenceId ( $id );
		$transfer->setData ( 'newsletter_id', $id );
		
		return $this;
	}

}