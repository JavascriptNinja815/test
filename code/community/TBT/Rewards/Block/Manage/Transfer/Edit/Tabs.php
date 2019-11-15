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
 * Manage Transfer Edit Tabs
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Manage_Transfer_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	/**
     * Main Constructor
     */
	public function __construct()
    {
		parent::__construct();
		$this->setId('transfer_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle(Mage::helper('rewards')->__('Transfer Information'));
	}

    /**
     * Prepare Tabs and Required Blocks in Layout
     * @return \TBT_Rewards_Block_Manage_Transfer_Edit_Tabs
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->addTab(
            'form_section',
            array(
                'label' => Mage::helper('rewards')->__( 'Transfer Information'),
                'title' => Mage::helper('rewards')->__('Transfer Information'),
                'content' => $this->getLayout()->createBlock('rewards/manage_transfer_edit_tab_form')->toHtml(),
                'active' => true
            )
        );

		$this->addTab(
            'customers_section',
            array(
                'label' => Mage::helper('rewards')->__('Customer'),
                'title' => Mage::helper('rewards')->__('Customer'),
                'content' => $this->getLayout()->createBlock('rewards/manage_transfer_edit_tab_customer_grid')->toHtml()
            )
        );

		$transfer = $this->_getTransfer();

		if ($transfer->isOrder ()) {
			if (Mage::getSingleton ( 'admin/session' )->isAllowed ( 'sales/order/actions/view' )) {
                $orderId = $transfer->getReferenceId();

                $transfer->setData( 'order_id', $orderId );
                Mage::unregister('transfer_data');
                Mage::register( 'transfer_data', $transfer );
                
				$this->addTab(
                    'orders_section',
                    array(
                        'label' => Mage::helper ( 'rewards' )->__ ( 'Reference Order' ),
                        'title' => Mage::helper ( 'rewards' )->__ ( 'Reference Order' ),
                        'content' => $this->getLayout ()->createBlock('rewards/manage_transfer_edit_tab_grid_orders', '', array('order_id' => $orderId))->toHtml(),
                        'after' => 'customers_section'
                    )
                );
			}
		}

        if ($transfer->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('product_review')) {
            $this->addTab(
                'reviews_section',
                array(
                    'label' => Mage::helper('rewards')->__('Reference Review/Rating'),
                    'title' => Mage::helper('rewards')->__('Reference Review/Rating'),
                    'content' => $this->getLayout()->createBlock('rewards/manage_transfer_edit_tab_grid_reviews')->toHtml(),
                    'after' => 'customers_section'
                )
            );
        }

        if ($transfer->getReasonId() == Mage::helper('rewards/transfer_reason')->getReasonId('tag')) {
			$this->addTab(
                'tags_section',
                array (
                    'label' => Mage::helper( 'rewards' )->__('Reference Product Tag'),
                    'title' => Mage::helper( 'rewards' )->__('Reference Product Tag'),
                    'content' => $this->getLayout()->createBlock('rewards/manage_transfer_edit_tab_grid_tags')->toHtml(),
                    'after' => 'customers_section'
                )
            );
		}
        
		if ($transfer->isPoll()) {
			$this->addTab(
                'polls_section',
                array(
                    'label' => Mage::helper('rewards')->__('Reference Poll'),
                    'title' => Mage::helper('rewards')->__('Reference Poll'),
                    'content' => $this->getLayout()->createBlock('rewards/manage_transfer_edit_tab_grid_polls')->toHtml()
                )
            );
		}

		if ($transfer->isFriendTransfer ()) {
			$this->addTab(
                'friends_section',
                array(
                    'label' => Mage::helper('rewards')->__('Reference Friend'),
                    'title' => Mage::helper('rewards')->__('Reference Friend'),
                    'content' => $this->getLayout()->createBlock('rewards/manage_transfer_edit_tab_grid_friends')->toHtml()
                )
            );
		}

		if ($transfer->isTransfer()) {
			$this->addTab(
                'reviews_section',
                array(
                    'label' => Mage::helper('rewards')->__('Reference Other Transfer'),
                    'title' => Mage::helper('rewards')->__('Reference Other Transfer'),
                    'content' => $this->getLayout()->createBlock('rewards/manage_transfer_edit_tab_grid_transfers')->toHtml()
                )
            );
		}

        return $this;
    }
	
	/**
	 * Fetches the transfer we want to edit.
	 *
	 * @return TBT_Rewards_Model_Transfer
	 */
	protected function _getTransfer()
    {
		return Mage::registry('transfer_data');
	}
}
