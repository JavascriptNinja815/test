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
 * @package    [TBT_RewardsReferral]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * Service class used to send referral invitations
 * @package     TBT_RewardsReferral
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Model_Service_Invite
    extends Varien_Object
{
    /**
     * Current Logged Rewards Customer Instance 
     * @var TBT_Rewards_Model_Customer
     */
    protected $_sessionCustomer;

    /**
     * Main Constructor
     */
    public function _construct()
    {
        parent::_construct();

        $this->_sessionCustomer = Mage::getSingleton('rewards/session')
            ->getSessionCustomer();
    }

    /**
     * Initialize Invitation Data from Current Request
     * @return \TBT_RewardsReferral_Model_Service_Invite
     */
    public function initDataFromRequest()
    {
        $this->clearInstance();
        $this->setName(Mage::app()->getRequest()->getPost('name'));
        $this->setInvitationMessage(Mage::app()->getRequest()->getPost('msg'));
        $this->setEmail(Mage::app()->getRequest()->getPost('email'));

        return $this;
    }

    public function setName($name)
    {
        $this->setData('name', trim((string) strip_tags($name)));

        return $this;
    }

    /**
     * Invitation Email Setter
     * @param string $email
     * @return \TBT_RewardsReferral_Model_Service_Invite
     */
    public function setEmail($email)
    {
        $this->setData('email', trim((string) strip_tags($email)));

        return $this;
    }

    /**
     * Invitation Message Setter
     * @param string $message
     * @return \TBT_RewardsReferral_Model_Service_Invite
     */
    public function setInvitationMessage($message)
    {
        $this->setData('invitation_message', trim((string) strip_tags($message)));

        return $this;
    }

    /**
     * Send Referral Invitation
     * @return boolean
     */
    public function sendInvitation($forceResend = false)
    {
        if (!$this->_isDataValid($forceResend)) {
            Mage::throwException($this->getInvalidMessage());
        }

        if (!$this->_sessionCustomer || !$this->_sessionCustomer->getId()) {
            $this->_sessionCustomer = Mage::getSingleton('rewards/session')
                ->getSessionCustomer();
        }

        $referralModel = Mage::getModel('rewardsref/referral');

        $subscriptionStatus = $referralModel->subscribe(
            $this->_sessionCustomer, $this->getEmail(), $this->getName(), $this->getInvitationMessage(), $forceResend
        );

        if (!$subscriptionStatus) {
            Mage::throwException(Mage::helper('rewardsref')
                ->__('There was a problem with the invitation.')
            );
        }

        return true;
    }

    /**
     * Clean Current Instance Params
     * @return \TBT_RewardsReferral_Model_Service_Invite
     */
    public function clearInstance()
    {
        $this->setData(array());

        return $this;
    }

    /**
     * Validate data and required conditions
     * @return boolean
     */
    protected function _isDataValid($forceResend = false)
    {
        if (!$this->getName()) {
            $this->setInvalidMessage(Mage::helper('rewardsref')
                ->__("Please enter your referral's name.")
            );
            return false;
        }

        if (!Zend_Validate::is($this->getEmail(), 'EmailAddress')) {
            $this->setInvalidMessage(Mage::helper('rewardsref')
                ->__('Please enter a valid email address.'));
            return false;
        }
        
        $referralModel = Mage::getModel('rewardsref/referral');
        
        if (!$forceResend && $referralModel->isSubscribed($this->getEmail())) {
            $this->setInvalidMessage(Mage::helper('rewardsref')
                ->__('You or somebody else has already invited %s.', $this->getEmail())
            );
            return false;
        }

        if ($this->_sessionCustomer && $this->_sessionCustomer->getEmail() === $this->getEmail()) {
            $this->setInvalidMessage(Mage::helper('rewardsref')
                ->__("%s is your own e-mail address.", $this->getEmail())
            );
        }

        $customer = Mage::getModel('rewards/customer')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
            ->loadByEmail($this->getEmail());

        if ($customer && $customer->getEmail() === $this->getEmail()) {
            $this->setInvalidMessage(Mage::helper('rewardsref')
                ->__("%s is already signed up to the store.", $this->getEmail())
            );
            return false;
        }

        return true;
    }
}