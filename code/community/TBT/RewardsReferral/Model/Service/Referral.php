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
 * Service class used to bind referral to referrer
 * @package     TBT_RewardsReferral
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Model_Service_Referral extends Varien_Object
{
    /**
     * Referrer Customer Instance
     * @var TBT_Rewards_Model_Customer
     */
    protected $_referrerCustomer;

    /**
     * Referral Customer Instance
     * @var TBT_Rewards_Model_Customer
     */
    protected $_referralCustomer;

    /**
     * Main Constructor
     * @param TBT_Rewards_Model_Customer $referrerCustomer
     * @param TBT_Rewards_Model_Customer $referralCustomer
     * @return \TBT_RewardsReferral_Model_Service_Referral
     */
    public function __construct($referrerCustomer = null, $referralCustomer = null)
    {
        parent::__construct();

        if ($referrerCustomer) {
            $this->_referrerCustomer = $referrerCustomer;
        }

        if ($referralCustomer) {
            $this->_referralCustomer = $referralCustomer;
        }

        return $this;
    }

    /**
     * Setter for Referrer Customer Instance
     * @param Mage_Customer_Model_Customer $referrerCustomer
     * @return \TBT_RewardsReferral_Model_Service_Referral
     */
    public function setReferrerCustomer(Mage_Customer_Model_Customer $referrerCustomer)
    {
        $this->_referrerCustomer = $referrerCustomer;

        return $this;
    }

    /**
     * Setter for Referral Customer Instance
     * @param Mage_Customer_Model_Customer $referralCustomer
     */
    public function setReferralCustomer(Mage_Customer_Model_Customer $referralCustomer)
    {
        $this->_referralCustomer = $referralCustomer;

        return $this;
    }

    /**
     * Bind Referral to Referrer
     * @return \TBT_RewardsReferral_Model_Service_Referral
     * @throws Exception
     */
    public function bind($triggerSignUpPoints = true)
    {
        if (!$this->_isBindingValid()) {
            throw new Exception('Referrer or Referral is not valid!');
        }

        $referral = Mage::getModel('rewardsref/referral');
        $referral->setReferralChildId($this->_referralCustomer->getId());

        $bindingAlreadyExist = $referral->referralExists($this->_referralCustomer->getId(), true);

        $referral->setReferralParentId($this->_referrerCustomer->getId())
            ->setReferralEmail($this->_referralCustomer->getEmail())
            ->setReferralName($this->_referralCustomer->getName());
        $referral->setDoCheckData(false);
        $referral->save();

        if ($triggerSignUpPoints && !$bindingAlreadyExist) {
            $signup = Mage::getModel('rewardsref/referral_signup');
            $signup->triggerEvent($this->_referralCustomer);
        }

        return $this;
    }

    /**
     * Check if there are referrer and referral objects
     * @return boolean
     */
    protected function _isBindingValid()
    {
        if (!$this->_referrerCustomer || !$this->_referrerCustomer->getId()) {
            return false;
        }

        if (!$this->_referralCustomer) {
            return false;
        }

        return true;
    }
}