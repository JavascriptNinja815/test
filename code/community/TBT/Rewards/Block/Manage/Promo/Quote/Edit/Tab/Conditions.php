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
 * Manage Promo Quote Edit Tab Conditions
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Manage_Promo_Quote_Edit_Tab_Conditions extends TBT_Rewards_Block_Manage_Promo_Quote_Edit_Tab_Abstract {
	
	protected function _prepareForm() {
		$model = Mage::registry ( 'current_promo_quote_rule' );
		
		$form = new Varien_Data_Form ();
		
		$form->setHtmlIdPrefix ( 'rule_' );
		
		$renderer = Mage::getBlockSingleton ( 'adminhtml/widget_form_renderer_fieldset' )->setTemplate ( 'promo/fieldset.phtml' );
		
		$renderer->setNewChildUrl ( $this->getUrl ( '*/manage_promo_quote/newConditionHtml/form/rule_conditions_fieldset' ) );
		
		$fieldset = $form->addFieldset ( 'conditions_fieldset', array ('legend' => Mage::helper ( 'salesrule' )->__ ( 'Apply the rule only if the following conditions are met (leave blank for all products)' ) ) )->setRenderer ( $renderer );
		
		$fieldset->addField ( 'conditions', 'text', array ('name' => 'conditions', 'label' => Mage::helper ( 'salesrule' )->__ ( 'Conditions' ), 'title' => Mage::helper ( 'salesrule' )->__ ( 'Conditions' ) ) )->setRule ( $model )->setRenderer ( Mage::getBlockSingleton ( 'rule/conditions' ) );
		
		/*
          $fieldset = $form->addFieldset('actions_fieldset', array(
          'legend'=>Mage::helper('salesrule')->__('Apply the rule to cart items matching the following conditions')
          ))->setRenderer($renderer);

          $fieldset->addField('actions', 'text', array(
          'name' => 'actions',
          'label' => Mage::helper('salesrule')->__('Apply to'),
          'title' => Mage::helper('salesrule')->__('Apply to'),
          'required' => true,
          ))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/actions'));
         */
		
		if ($this->_isRedemptionType ()) {
			$this->_getPointsActionFieldset ( $form );
		}
		
		$form->setValues ( $model->getData () );
		
		$this->setForm ( $form );
		
		return parent::_prepareForm ();
	}

}