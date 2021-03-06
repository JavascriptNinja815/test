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
 * Manage Promo Catalog Redemptions
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Manage_Promo_Catalog_Redemptions extends TBT_Rewards_Block_Manage_Widget_Grid_Container {
	
	protected $_blockGroup = 'rewards';
	const RULE_TYPE = TBT_Rewards_Helper_Rule_Type::REDEMPTION;
	
	public function __construct() {
		$this->_addButton ( 'apply_rules', array ('label' => Mage::helper ( 'catalogrule' )->__ ( 'Apply Rules' ), 'onclick' => "location.href='" . $this->getUrl ( '*/*/applyRules/type/' . self::RULE_TYPE . '/' ) . "'", 'class' => '' ) );
		
		$this->_controller = 'manage_promo_catalog_redemptions';
		$this->_blockGroup = "rewards";
		$this->_headerText = Mage::helper ( 'rewards' )->__ ( 'Catalog Points Spending Rules' );
		$this->_addButtonLabel = Mage::helper ( 'rewards' )->__ ( 'Add New Catalog Spending Rule' );
		parent::__construct ();
	}
	
	public function getCreateUrl() {
		return $this->getUrl ( '*/*/new', array ('type' => self::RULE_TYPE ) );
	}

}