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
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Checkout_Cart_Slider extends TBT_Rewards_Block_Points_Slider {
	
	/**
	 * Before rendering html, but after trying to load cache
	 *
	 * @return Mage_Core_Block_Abstract
	 */
	protected function _beforeToHtml() {
		$this->loadSliderSettings ();
		return parent::_beforeToHtml ();
	}
	
	public function loadSliderSettings() {
		//TODO if there are multiple rules 
		$quote = Mage::getSingleton ( 'rewards/session' )->getQuote ();
		$this->setPointsStep ( $quote->getPointsStep () );
		$this->setMinSpendablePoints ( $quote->getMinSpendablePoints () );
		$this->setMaxSpendablePoints ( $quote->getMaxSpendablePoints () );
		
		return $this;
	}
	
	public function getCurrentSpendingPoints() {
		//@nelkaake Changed on Wednesday May 5, 2010: 
		$quote = Mage::getSingleton ( 'rewards/session' )->getQuote ();
		$uses = $quote->getPointsSpending();
		$uses = min ( $quote->getMaxSpendablePoints (), $uses );
		return $uses;
	}

}
