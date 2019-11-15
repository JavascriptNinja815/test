<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the SWEET TOOTH POINTS AND REWARDS
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
 */

/**
 * Admin Customer Left Menu
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Adminhtml_Customer_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs 
{
    public function __construct() 
    {
        parent::__construct ();
        $this->setId('customer_info_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('customer')->__('Customer Information'));
    }

    /**
     * This overwrites the parent function to add the 'Points & Rewards' 
     * tab in the edit menu for the customer.
     */
    protected function _beforeToHtml() 
    {
        if (Mage::registry('current_customer')->getId()) {
            $this->addTab('view', array('label' => Mage::helper('customer')->__('Customer View'), 'content' => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_view')->toHtml(), 'active' => true));
        }

        $this->addTab('account', array('label' => Mage::helper('customer')->__('Account Information'), 'content' => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_account')->initForm()->toHtml(), 'active' => Mage::registry('current_customer')->getId() ? false : true));
        $this->addTab('addresses', array('label' => Mage::helper('customer')->__('Addresses'), 'content' => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_addresses')->initForm()->toHtml()));

        if (Mage::registry('current_customer')->getId()) {
            $this->addTab('orders', array('label' => Mage::helper('customer')->__('Orders'), 'class' => 'ajax', 'url' => $this->getUrl('*/*/orders', array('_current' => true))));
            $this->addTab('cart', array('label' => Mage::helper('customer')->__('Shopping Cart'), 'class' => 'ajax', 'url' => $this->getUrl('*/*/carts', array('_current' => true))));
            $this->addTab('wishlist', array('label' => Mage::helper('customer')->__('Wishlist'), 'class' => 'ajax', 'url' => $this->getUrl('*/*/wishlist', array('_current' => true))));
            $this->addTab('newsletter', array('label' => Mage::helper('customer')->__('Newsletter'), 'content' => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_newsletter')->initForm()->toHtml()));
            $this->addTab('reviews', array('label' => Mage::helper('customer')->__('Product Reviews'), 'class' => 'ajax', 'url' => $this->getUrl('*/*/productReviews', array('_current' => true))));
            $this->addTab('tags', array('label' => Mage::helper('customer')->__('Product Tags'), 'class' => 'ajax', 'url' => $this->getUrl('*/*/productTags', array('_current' => true))));
            $this->addTab('rewards', array('label' => Mage::helper('customer')->__('Points & Rewards'), 'content' => $this->getLayout()->createBlock('rewards/manage_customer_edit_tab_main')->toHtml()));
        }

        $this->_updateActiveTab();
        Varien_Profiler::stop('customer/tabs');
        return parent::_beforeToHtml();
    }
}

