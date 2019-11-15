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
 *
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Manage Transfer Edit Tab Grid Email Preferences
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Manage_Customer_Edit_Tab_Emails extends Mage_Adminhtml_Block_Template
{	
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('rewards/customer/edit/tab/emailpreferences.phtml');
    }
	
    /**
     * Does the logged in customer have loyalty emails enabled?
     * @return bool
     */
    public function hasEmailsEnabled()
    {
        $customer = $this->getCustomer();
        if ($customer && $customer->getId()) {
            return (bool)$customer->getRewardsPointsNotification();
        }
        
        return false;
    }
    
    /**
     * Get checkbox label
     * @return string
     */
    public function getLabel()
    {
        $currencyHelper = Mage::helper('rewards/currency');
        $defaultCurrencyId = $currencyHelper->getDefaultCurrencyId();
        $pointsCaption = $currencyHelper->getFullCurrencyCaption($defaultCurrencyId);
        
        if (Mage::getStoreConfigFlag('rewards/pointSummaryEmails/allow_points_summary_email')) {
            return $this->__("Receives %s Earning Notifications & Monthly %s Summaries", $pointsCaption, $pointsCaption);
        } else {
            return $this->__("Receives %s Earning Notifications", $pointsCaption);
        }
    }
    
    public function getCustomer() 
    {
        if ($this->hasCustomer()) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer(
                $this->getData('customer')
            );
        }
        
        if (Mage::registry('current_customer')) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer(
                Mage::registry('current_customer')
            );
        }
        
        if (Mage::registry('customer')) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer(
                Mage::registry('customer')
            );
        }
        
        Mage::throwException(
            Mage::helper('customer')->__('Cannot find customer.')
        );
    }
}