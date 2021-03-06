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
 * Customer Index Controller
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Customer_IndexController extends Mage_Core_Controller_Front_Action {
	
    protected function _isAllowed() {
        return Mage::getSingleton ( 'admin/session' )->isAllowed ( 'rewards/customer/customer' );
    }
    
	public function indexAction() {
		$this->loadLayout ();
		
		$cust = Mage::getModel('rewards/customer')->getRewardsCustomer(Mage::registry ( 'customer' ));
		if (! $cust->getId ()) {
			$this->redirect ( 'customer' );
			return;
		}
		
		$rewards_customer = Mage::getSingleton ( 'rewards/session' )->getSessionCustomer ();
		if (! Mage::getSingleton ( 'rewards/session' )->isCustomerLoggedIn ()) {
			Mage::getSingleton ( 'customer/session' )->addError ( $this->__ ( 'Sorry, you are not authorized to be access this section.' ) );
			$this->redirect ( '*/*/' );
			return;
		}
		
		Mage::register ( 'customer', $rewards_customer );
		
		$this->renderLayout ();
	}
	
	public function preDispatch() {
		parent::preDispatch ();
		
		if (! Mage::getSingleton ( 'customer/session' )->authenticate ( $this )) {
			$this->setFlag ( '', 'no-dispatch', true );
			if (! Mage::getSingleton ( 'customer/session' )->getBeforeWishlistUrl ()) {
				Mage::getSingleton ( 'customer/session' )->setBeforeWishlistUrl ( $this->_getRefererUrl () );
			}
		}
		if (! Mage::getStoreConfigFlag ( 'wishlist/general/active' )) {
			$this->norouteAction ();
			return;
		}
	}

}