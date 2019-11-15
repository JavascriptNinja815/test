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
 * Manage Transfer Edit Tab Form
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Manage_Transfer_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form {
	
	protected function _prepareForm() {
		
		if (Mage::getSingleton ( 'adminhtml/session' )->getTransferData ()) {
			$formData = Mage::getSingleton ( 'adminhtml/session' )->getTransferData ();
		} elseif (Mage::registry ( 'transfer_data' )) {
			$formData = Mage::registry ( 'transfer_data' )->getData ();
		} else {
			$formData = array ();
		}
		
		//Make sure the loaded value is always positive
		if (isset ( $formData ['quantity'] )) {
			if ($formData ['quantity'] < 0) {
				$formData ['quantity'] = - $formData ['quantity'];
				$formData ['transfer_style'] = 'deduct';
			} else {
				$formData ['transfer_style'] = 'give';
			}
		}
		
		if (! isset ( $formData ['status_id'] )) {
                        $formData ['status_id'] = null;
                }
                
		$availStatuses = Mage::getSingleton ( 'rewards/transfer_status' )->getAvailStatuses ( $formData ['status_id'] );
		
		// If pending time status is selectable, then turn it off.  Manual points transfers with pending time status are not available yet.
		if(empty($formData ['status_id'])) {
		    if(isset($availStatuses[TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_TIME])) {
		        unset($availStatuses[TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_TIME]);
		    }
		    // Humans should not be able to use the pending event status
		    if(isset($availStatuses[TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT])) {
		        unset($availStatuses[TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT]);
		    }
		}
		
		$form = new Varien_Data_Form ();
		$this->setForm ( $form );
		$fieldset = $form->addFieldset ( 'transfer_form', array ('legend' => Mage::helper ( 'rewards' )->__ ( 'Transfer Information' ) ) );
		
		$fieldset->addField ( 'transfer_style', 'select', array ('label' => Mage::helper ( 'rewards' )->__ ( 'Transfer Style' ), 'title' => Mage::helper ( 'rewards' )->__ ( 'Transfer Style' ), 'name' => 'transfer_style', 'required' => true, 'class' => 'required-entry wikihints-justify', 'options' => array ('give' => Mage::helper ( 'rewards' )->__ ( 'Give Points' ), 'deduct' => Mage::helper ( 'rewards' )->__ ( 'Deduct Points' ) ) ) );
		
		$fieldset->addField ( 'quantity', 'text', array (
			'name' => 'quantity', 
			'required' => true, 
			'class' => 'required-entry validate-not-negative-number wikihints-justify', 
			'label' => Mage::helper ( 'salesrule' )->__ ( 'Points Amount' ) 
		) );
		
		if (sizeof($availStatuses) == 1) {
		    $status_field_type = 'hidden';
		} else {
		    $status_field_type = 'select';
		}
		
		$status_field = $fieldset->addField ( 'status_id', $status_field_type, array (
			'label' => Mage::helper ( 'rewards' )->__ ( 'Status ID' ), 
			'title' => Mage::helper ( 'rewards' )->__ ( 'Status ID' ), 
			'name' => 'status_id',
			'options' => $availStatuses, 
			'class' => 'required-entry wikihints-justify', 
			'required' => true
	    ) );
	    
		if (sizeof($availStatuses) == 1) {
		    $availStatuses_copy = $availStatuses;
		    $status_label_value =array_pop($availStatuses_copy);
		    $formData['status_label'] = $status_label_value;
		    
		    
    		$status_field = $fieldset->addField ( 'status_label', 'label', array (
    			'label' => Mage::helper ( 'rewards' )->__ ( 'Status' ), 
    			'title' => Mage::helper ( 'rewards' )->__ ( 'Status' ), 
    			'name' => 'status_label',
    			'class' => 'wikihints-justify', 
    		    'value' => $status_label_value
    	    ) );
    	    
		}
	    
	    Mage::getSingleton('rewards/wikihints')->addWikiHint($status_field, "article/384-configuration-initial-points-transfer-statuses", "Edit Points Transfer - Status" );

                $reasonHelper = Mage::helper('rewards/transfer_reason');
                
                if ($formData && !isset($formData['reason_id'])) {
                    $formData['reason_id'] = $reasonHelper->getReasonId('adjustment');
                }

		$formData['reason_label'] = $reasonHelper->getReasonLabel($formData['reason_id']);
                
                $fieldset->addField ( 'reason_id', 'hidden', array (
                    'label' => Mage::helper ( 'rewards' )->__ ( 'Reason' ), 
                    'title' => Mage::helper ( 'rewards' )->__ ( 'Reason' ), 
                    'name' => 'reason_id', 
                    'class' => 'required-entry wikihints-justify'
                ));
            
		$fieldset->addField ( 'reason_label', 'label', array (
                    'label' => Mage::helper ( 'rewards' )->__ ( 'Reason' ), 
                    'title' => Mage::helper ( 'rewards' )->__ ( 'Reason' ), 
                    'name' => 'reason_id', 
                    'class' => 'required-entry wikihints-justify'
                ));
		
		$comments_field = $fieldset->addField ( 'comments', 'editor', array (
			'name' => 'comments', 'label' => Mage::helper ( 'rewards' )->__ ( 'Comments/Notes' ), 
			'title' => Mage::helper ( 'rewards' )->__ ( 'Comments/Notes' ), 
			'style' => 'width:88%; height:200px;', 
			'class' => 'wikihints-justify' ) 
	    );
		
	    Mage::getSingleton('rewards/wikihints')->addWikiHint($comments_field, "article/298-edit-points-transfer-transfer-comments", "Edit Points Transfer - Transfer Comments" );
		
		$form->setValues ( $formData );
		Mage::getSingleton ( 'adminhtml/session' )->getTransferData ( null );
		
		return parent::_prepareForm ();
	}

}