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
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Order Type Source Model
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Model_Source_OrderType
{
    const TYPE_ALL_ORDERS = 1;
    const TYPE_ORDERS_WITH_EARNINGS = 2;
    const TYPE_ORDERS_WITH_SPENDINGS = 3;
    const TYPE_LOYALTY_ORDERS = 4;
    const TYPE_ORDERS_FROM_REFERRALS = 5;
    
    protected $_options;
    
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = array(
                array(
                    'label' => Mage::helper('tbtreports')->__('All Orders'),
                    'value' => self::TYPE_ALL_ORDERS
                ),
                array(
                    'label' => Mage::helper('tbtreports')->__('Orders with Earnings'),
                    'value' => self::TYPE_ORDERS_WITH_EARNINGS
                ),
                array(
                    'label' => Mage::helper('tbtreports')->__('Orders with Spendings'),
                    'value' => self::TYPE_ORDERS_WITH_SPENDINGS
                ),
                array(
                    'label' => Mage::helper('tbtreports')->__('Orders from Loyalty Customers'),
                    'value' => self::TYPE_LOYALTY_ORDERS
                ),
                array(
                    'label' => Mage::helper('tbtreports')->__('Orders from Referred Customers'),
                    'value' => self::TYPE_ORDERS_FROM_REFERRALS
                )
            );
        }
        return $this->_options;
    }
    
    public function getAllOptions()
    {
        $options = array();
        foreach ($this->toOptionArray() as $option) {
            $options[$option['value']] = $option['label'];
        }
        
        return $options;
    }
    
    public function getLabel($value)
    {
        $options = $this->getAllOptions();
        
        if (empty($options[$value])) {
            return null;
        }
        
        return $options[$value];
    }
}

