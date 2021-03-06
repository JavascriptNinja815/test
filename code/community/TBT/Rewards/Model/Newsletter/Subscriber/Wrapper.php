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
 * Newsletter
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Newsletter_Subscriber_Wrapper extends Varien_Object {
	
	/**
	 * @var Mage_Newsletter_Model_Subscriber
	 */
	protected $_subscriber = null;
	
	/**
	 * @var TBT_Rewards_Model_Customer
	 */
	protected $_subscribedCustomer = null;
	
	/**
	 * @param Mage_Newsletter_Model_Subscriber $subscriber
	 */
	public function wrap(Mage_Newsletter_Model_Subscriber &$subscriber) {
		$this->_subscriber = $subscriber;
		return $this;
	}
	
	/**
	 * @return Mage_Newsletter_Model_Subscriber
	 */
	public function getOriginalModel() {
		return $this->_subscriber;
	}
	
	/**
	 * Pseudo newsletter ID since Magento only has one newsletter for the time being.
	 *
	 * @return integer
	 */
	public function getNewsletterId() {
		return 1;
	}
	
	/**
	 * Fetches the rewards customer trying to subscribe
	 *
	 * @return TBT_Rewards_Model_Customer
	 */
	public function getCustomer() {
		if ($this->_subscribedCustomer == null) {
			$customer_id = $this->_subscriber->getCustomerId ();
			$this->_subscribedCustomer = Mage::getModel ( 'rewards/customer' )->load ( $customer_id );
		}
		return $this->_subscribedCustomer;
	}
	
	/**
	 * @alias getCustomer()
	 */
	public function getRewardsCustomer() {
		return $this->getCustomer ();
	}
	
	/**
	 * True if the customer has received points for the newsletter
	 * @param integer $newsletter_id
	 * @return boolean
	 */
	public function customerHasPointsForNewsletter() 
        {
            $collection = Mage::getModel('rewards/transfer')->getCollection()
                ->addFieldToFilter('customer_id', $this->_subscriber->getCustomerId())
                ->addFieldToFilter('reason_id', Mage::helper('rewards/transfer_reason')->getReasonId('newsletter'));
            
            return ($collection->getSize() > 0);
	}
	
	/**
	 * @return Mage_Newsletter_Model_Subscriber
	 */
	public function getSubscriber() {
		return $this->getOriginalModel ();
	}

}

