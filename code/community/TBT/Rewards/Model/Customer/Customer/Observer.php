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
 * @deprecated
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Customer_Customer_Observer extends Varien_Object
{

	/**
	 * @var int
	 */
	protected $oldId = -1;

	/**
	 * AfterLoad for customer
	 * @param Varien_Event_Observer $observer
	 */
	public function customerAfterLoad(Varien_Event_Observer $observer)
	{
		if(! Mage::helper('rewards/version')->isBaseMageVersionAtLeast("1.3.3.0")) {
		    $review = $o->getEvent ()->getObject ();
		}
        $customer = $this->_loadCustomer(Mage::helper('rewards/dispatch')->getEventObject($observer));
        $customer->loadCollections();
    }

    /**
     * AfterSave for customer
     * @param Varien_Event_Observer $observer
     */
    public function customerAfterSave(Varien_Event_Observer $observer) {
        $customer_obj = $observer->getEvent()->getCustomer();
        $customer = Mage::getModel('rewards/customer')->getRewardsCustomer($customer_obj);
        //If the customer is new (hence not having an id before) get applicable rules,
        //and create a transfer for each one
        $isNew = false;
        if ( $customer->isNewCustomer($customer->getId()) ) {
            $isNew = true;
            $this->oldId = $customer->getId(); //This stops multiple triggers of this function
            $customer->createTransferForNewCustomer(); //@TODO Change to separate transfer model
        }
        Mage::getSingleton('rewards/session')->setCustomer($customer);
        if ( $isNew ) {
            Mage::getSingleton('rewards/session')->triggerNewCustomerCreate($customer);
            Mage::dispatchEvent('rewards_new_customer_create', array(
                'customer' => &$customer
            ));
        }
    }

	/**
	 * BeforeSave for customer
	 * @param Varien_Event_Observer $observer
	 */
	public function customerBeforeSave(Varien_Event_Observer $observer)
	{
        $customer = $this->_loadCustomer(Mage::helper('rewards/dispatch')->getEventObject($observer));
        $oldId = $customer->getId();
        if ( ! empty($oldId) ) {
            $this->oldId = $oldId;
        }
	}

	/**
     * True if the id specified is new to this customer model after a SAVE event.
     *
     * @param integer $checkId
     * @return boolean
     */
    public function isNewCustomer($checkId)
    {
        return ($this->oldId != $checkId);
    }

	/**
	 * Loads the customer wrapper
	 * @param Mage_Customer_Model_Customer $customer
	 * @return TBT_Rewards_Model_Customer_Customer_Wrapper
	 */
	private function _loadCustomer(Mage_Customer_Model_Customer $customer)
	{
	    return Mage::getModel('rewards/customer')->getRewardsCustomer($customer);
	}

}