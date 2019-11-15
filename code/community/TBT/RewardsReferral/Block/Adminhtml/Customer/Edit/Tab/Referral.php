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
 * @package    [TBT_RewardsReferral]
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * This class is used to define a referral tab for Admin Customer View
 * @package     TBT_RewardsReferral
 * @subpackage  Block
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Block_Adminhtml_Customer_Edit_Tab_Referral
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Tab Label
     * @return string
     */
    public function getTabLabel()
    {
        return $this->__ ( "Referred By" );
    }

    /**
     * Tab Title
     * @return string
     */
    public function getTabTitle()
    {
        return $this->__ ( "Referred By" );
    }

    /**
     * Show Customer Referral Tab
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Is tab Hidden
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Retrieve available customer
     *
     * @return TBT_Rewards_Model_Customer
     */
    public function getCustomer()
    {
        if ($this->hasCustomer ()) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer($this->getData ( 'customer' ));
        }

        if (Mage::registry ( 'current_customer' )) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer(Mage::registry ( 'current_customer' ));
        }

        if (Mage::registry ( 'customer' )) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer(Mage::registry ( 'customer' ));
        }

        return Mage::getModel('rewards/customer');
    }
}
