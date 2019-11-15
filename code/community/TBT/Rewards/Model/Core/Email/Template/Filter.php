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
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Email Template Filter Model
 * @nelkaake 31/01/2010 11:09:01 PM
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */

class TBT_Rewards_Model_Core_Email_Template_Filter extends Mage_Core_Model_Email_Template_Filter 
{
    /**
     * Retrieve the Sweet Tooth points balance directive
     * 
     * @param array $construction
     * @return string
     */
    public function stbalanceDirective($construction)
    {
        $params = $this->_getIncludeParameters($construction[2]);
        $customer = Mage::getModel("customer/customer"); 
        
        if (isset($params['customer_id'])) {
            $customer = $customer->load($params['customer_id']);
        } elseif (isset($params['email'])) {
            $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
            $customer->loadByEmail($params['email']);
        }
        
        if ($customer->getId()) {
            $rewardsCustomer = Mage::getModel('rewards/customer')->getRewardsCustomer($customer);
            return $rewardsCustomer->getPointsSummary();
        }
        
        return '';
    }
}
