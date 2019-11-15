<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
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
 * If you did not receive a copy of the license, please send an email to
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy
 * immediately.
 *
 * @category   [TBT]
 * @package    [TBT_RewardsReferral]
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class TBT_RewardsReferral_Block_Manage_Customer_Edit_Tab_Info extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface 
{
    /**
     * Prepare form
     * @return $this
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form ();
        $form->setHtmlIdPrefix('referral_info_');
        $fieldset = $form->addFieldset('points_action_fieldset', array('legend' => Mage::helper('rewards')->__('Referral Info')));
        
        $fieldset->addField('referral_url', 'text', array(
            'name' => 'referral_url', 
            'label' => Mage::helper('rewards')->__('Referral URL'),
            'value' => $this->getReferralUrl()
        ));
        
        $fieldset->addField('referral_code', 'text', array(
            'name' => 'referral_code', 
            'label' => Mage::helper('rewards')->__('Referral Code'),
            'value' => $this->getReferralCode()
        ));
        
        $fieldset->addField('referral_email', 'text', array(
            'name' => 'referral_email', 
            'label' => Mage::helper('rewards')->__('Referral Email'),
            'value' => $this->getReferralEmail()
        ));
        
        $this->setForm($form);
        return $this;
    }
    
    
    /**
     * Fetches the rewards customer for this session
     * @return TBT_Rewards_Model_Customer
     */
    public function getCustomer() 
    {
        if ($this->hasCustomer()) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer($this->getData('customer'));
        }
        
        if (Mage::registry('current_customer')) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer(Mage::registry('current_customer'));
        }
        
        if (Mage::registry('customer')) {
            return Mage::getModel('rewards/customer')->getRewardsCustomer(Mage::registry('customer'));
        }
        
        Mage::throwException(Mage::helper('customer')->__('Can\'t get customer instance'));
    }
    
    /**
     * Fetch referral id
     * @return int
     */
    public function getReferralId()
    {
        return $this->getCustomer()->getId();
    }
    
    /**
     * fetch referral email
     * @return string
     */
    public function getReferralEmail()
    {
        return (string)$this->getCustomer()->getEmail();
    }

    /**
     * Fetch referral code
     * @return string
     */
    public function getReferralCode()
    {
        return (string)Mage::helper('rewardsref/code')->getCode($this->getReferralId());
    }

    /**
     * Fetch referral url
     * @return string
     */
    public function getReferralUrl()
    {
        return (string)Mage::helper('rewardsref/url')->getUrl($this->getCustomer());
    }
    
    /**
    * ######################## TAB settings #################################
    */
    public function getTabLabel() 
    {
        return $this->__("Referral Information");
    }

    public function getTabTitle() 
    {
        return $this->__("Referral Information");
    }

    public function canShowTab() 
    {
        return true;
    }

    public function isHidden() 
    {
        return false;
    }
}

