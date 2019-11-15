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
 * @package    [TBT_Reports]
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Manage Loyalty Container
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Block_Adminhtml_Loyalty_Referral extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'tbtreports';
        $this->_controller = 'adminhtml_loyalty_referral';
        $this->_headerText = Mage::helper('tbtreports')->__('Orders from Referred Customers');
 
        parent::__construct();
        $this->_removeButton('add');
    }
    
    protected function _prepareLayout()
    {
        $reportCode = Mage::app()->getRequest()->getParam('report_code');
        $isSecure = (bool) Mage::app()->getStore()->isCurrentlySecure();
        
        $this->_addButton('add_new', array(
            'label'   => Mage::helper('catalog')->__('Back to MageRewards Dashboard'),
            'onclick' => "setLocation('{$this->getUrl('adminhtml/rewardsDashboard/index', array('report_code' => $reportCode, '_secure' => $isSecure))}')",
            'class'   => 'back'
        ));
        
        return parent::_prepareLayout();
    }
}

