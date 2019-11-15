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
 * Code generator/parser helper
 *
 * @nelkaake Added on Saturday June 26, 2010:  
 * @category   TBT
 * @package    TBT_RewardsReferral
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Helper_Code extends Mage_Core_Helper_Abstract
{
    /**
     * Get email from encrypted code (long code or hashid)
     * @param string $code
     * @return string
     */
    public function getEmail($code)
    {
        $hashIdsHelper = Mage::helper('rewards/hashids');
        $shortcodeHelper = Mage::helper('rewardsref/shortcode');

        /**
         * @deprecated Shortcode match for backwards compatibility
         */
        if (is_numeric($code) && $shortcodeHelper->isValid($code)) {
            $customerId = $shortcodeHelper->getCustomerId($code);
            $customer = Mage::getModel('rewards/customer')
                ->setWebsiteId(Mage::app()->getWebsite()->getId())
                ->load($customerId);
            
            return $customer->getEmail();
        }

        /**
         * @deprecated old encryption method, backwards compatibility check
         */
        $decryptedCode = base64_decode($code);
        $email = $this->_getEncrypter()->decrypt($decryptedCode);

        if ($this->check_email_address($email)) {
            return $email;
        }


        $decryptedCode = $hashIdsHelper->decryptIds($code);

        $customer = Mage::getModel('rewards/customer')
            ->setWebsiteId(Mage::app()->getWebsite()->getId())
            ->load($decryptedCode);

        if ($customer && $customer->getId()) {
            return $customer->getEmail();
        }

        return '';
    }

    /**
     * Get code by email or customer id
     * @param int|string $emailOrCustomerId
     * @return type
     */
    public function getCode($emailOrCustomerId)
    {
        $hashIdsHelper = Mage::helper('rewards/hashids');

        $code = '';

        if (is_numeric($emailOrCustomerId)) {
            $code = $hashIdsHelper->cryptIds($emailOrCustomerId);
        } else {
            $customer = Mage::getModel('rewards/customer')
                ->setWebsiteId(Mage::app()->getWebsite()->getId())
                ->loadByEmail($emailOrCustomerId);

            if ($customer && $customer->getId()) {
                $code = $hashIdsHelper->cryptIds($customer->getId());
            }
        }

        return $code;
    }

    /**
     * Long code generator
     * @deprecated since version 1.9.0.0
     * @param string $email
     * @return string
     */
    public function getLongCode($email)
    {
        $code = $this->_getEncrypter()->encrypt($email);
        $code = base64_encode($code);
        return $code;
    }
    
    //@nelkaake Added on Saturday June 26, 2010: 
    protected function _getEncrypter() {
        return Mage::getSingleton('core/encryption');
    }

    /**
     * returns true if this is a valid e-mail address
     * @param string $email
     */
    public function check_email_address($email) {
        return Mage::helper('rewardsref/validation')->isValidEmail($email);
    }
    
    /**
     * Return back an e-mail address from a referral code/e-mail that is provided.
     * @deprecated !! will be removed within next releases
     * @param string $refstr
     * @return string
     */
    public function parseEmailFromReferralString($refstr) {
        if ($this->check_email_address($refstr)) {
            $email = strtolower(trim($refstr));
        } elseif ( Mage::helper('rewardsref/shortcode')->isValid($refstr) ) {
            $email = Mage::helper('rewardsref/shortcode')->getEmail($refstr) ;
        } else {
            $email = $this->getEmail($refstr);
        }
        return $email;
    }

    /**
     * Set Referrer in session
     * @deprecated !! will be removed within next releases
     * @param string $referral_code_or_email
     * @return \TBT_RewardsReferral_Helper_Code
     */
    public function setReferral($referral_code_or_email) {
        //@nelkaake Added on Thursday July 8, 2010:
        $email = Mage::helper('rewardsref/code')->parseEmailFromReferralString($referral_code_or_email);
        Mage::getSingleton('core/session')->setReferrerEmail($email);
        return $this;
    }

    //@nelkaake Added on Wednesday October 6, 2010: Gets the referral into the session
    public function getReferral() {
        return Mage::getSingleton('core/session')->getReferrerEmail();
    }


    /**
     *
     * @param Mage_Customer_Model_Customer $referrerInstanceOrCodeOrEmail
     * @return \TBT_RewardsReferral_Helper_Code
     */
    public function setReferrer($referrerInstanceOrCodeOrEmail)
    {
        $email = false;
        $customerId = false;
        
        $shortcodeHelper = Mage::helper('rewardsref/shortcode');
        $hashIdsHelper = Mage::helper('rewards/hashids');

        /**
         * Get customer ID from Customer Instance
         */
        if (
            $referrerInstanceOrCodeOrEmail instanceof Mage_Customer_Model_Customer
            && $referrerInstanceOrCodeOrEmail->getId()
        ) {
            $customerId = $referrerInstanceOrCodeOrEmail->getId();
        }

        /**
         * Get customer ID (if not found already) from Email
         */
        if (!$customerId && $this->check_email_address($referrerInstanceOrCodeOrEmail)) {
            $email = strtolower(trim($referrerInstanceOrCodeOrEmail));
            $rewardsCustomer = Mage::getModel('rewards/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);

            if ($rewardsCustomer && $rewardsCustomer->getId()) {
                $customerId = $rewardsCustomer->getId();
            }
        }

        /**
         * Get Customer ID (if not found already) from Shortcode
         */
        if (!$customerId && $shortcodeHelper->isValid($referrerInstanceOrCodeOrEmail)) {
            $customerId = $shortcodeHelper->getCustomerId($referrerInstanceOrCodeOrEmail);
        }

        /**
         * Get Customer ID (if not found already) from Longcode
         */
        if (!$customerId) {
            $decryptedCode = base64_decode($referrerInstanceOrCodeOrEmail);
            $emailFromLongCode = $this->_getEncrypter()->decrypt($decryptedCode);

            if ($this->check_email_address($emailFromLongCode)) {
                $rewardsCustomer = Mage::getModel('rewards/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($emailFromLongCode);

                if ($rewardsCustomer && $rewardsCustomer->getId()) {
                    $customerId = $rewardsCustomer->getId();
                }
            }
        }

        /**
         * Get Customer ID (if not found already) from Hashid
         */
        if (!$customerId && is_string($referrerInstanceOrCodeOrEmail)) {
            $decryptedCode = $hashIdsHelper->decryptIds($referrerInstanceOrCodeOrEmail);

            $customerId = is_numeric($decryptedCode) ? $decryptedCode : false;
        }

        if ($customerId) {
            Mage::getSingleton('core/session')->setReferrerCustomerId($customerId);
        }

        return $this;
    }

    /**
     * Get Referrer from Session
     * 
     * @param boolean $instanceFlag {Used as a flag to decide if method returns an id or an instance of customer}
     * @return null|TBT_Rewards_Model_Customer|int
     */
    public function getReferrer($instanceFlag = false)
    {
        $referrerCustomerId = Mage::getSingleton('core/session')->getReferrerCustomerId();

        if (!$referrerCustomerId) {
            return $referrerCustomerId;
        }

        if ($instanceFlag) {
            $referrerCustomer = Mage::getModel('rewards/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->load($referrerCustomerId);
            return $referrerCustomer;
        }

        return $referrerCustomerId;
    }


    /**
     * Fetches the affiliate customer model from the session if it exists
     * @return TBT_Rewards_Model_Customer
     */
    public function getReferringCustomer() {
        $affiliate_email = $this->getReferral();
        $affiliate = Mage::getModel( 'rewards/customer' )->setStore( Mage::app()->getStore() );
        
        if(empty($affiliate_email)) {
            return $affiliate;
        }
        
        $affiliate->loadByEmail($affiliate_email);
        
        return $affiliate;
    }
}
