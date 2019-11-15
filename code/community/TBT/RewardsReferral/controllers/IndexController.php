<?php
/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL: 
 *      https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL: 
 *      http://opensource.org/licenses/osl-3.0.php
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
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Customer Controller
 *
 * @category   TBT
 * @package    TBT_RewardsReferral
 * @nelkaake Added on Saturday June 26, 2010:  
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_IndexController extends Mage_Core_Controller_Front_Action 
{
    public function indexAction() 
    {
        $this->loadLayout();

        $customer = Mage::getSingleton('rewards/session')->getRewardsCustomer();
        Mage::register('customer', $customer);

        $this->renderLayout();

        return $this;
    }

    public function referAction()
    {
        try {
            $email = $this->getRequest()->get("email", null);
            $email = urldecode($email);

            $code = $this->getRequest()->get("st-code", null);

            if (!$code) {
                $code = $this->getRequest()->get("code", null);
            }

            $code = urldecode($code);

            $referrer_id = $this->getRequest()->get("st-id", null);

            if (!$referrer_id) {
                $referrer_id = $this->getRequest()->get('id', null);
            }

            $referrer_id = urldecode($referrer_id);

            if (empty($email) && empty($code) && empty($referrer_id)) {
                throw new Exception($this->__('Please specify either a referral e-mail address or referral code.'));
            }

            if (empty($email) && empty($referrer_id)) {
                $email = Mage::helper('rewardsref/code')->getEmail($code);
            } elseif (empty($email) && empty($code)) {
                $email = Mage::getModel('rewards/customer')->load($referrer_id)->getEmail();
            } elseif (empty($email)) {
                throw new Exception($this->__('Please specify either a referral e-mail address or referral code.'));
            }

            $referrer = Mage::helper('rewardsref/code')->setReferrer($email);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addNotice(
                $this->__("Provided referral code is not valid.")
            );
        }

        $this->_redirectAffiliate();
        return $this;
    }

    /**
     * Redirects the affiliate that came to this page through some predefined URL
     * to another URL based on the config.
     */
    protected function _redirectAffiliate() 
    {
        $redirect_path = Mage::helper('rewardsref/config')->getRedirectPath(Mage::app()->getStore()->getId());
        $this->getResponse()->setRedirect($redirect_path, 301);
        return $this;
    }

    public function getCurrentRefEmailAction() 
    {
        try {
            $email = Mage::getSingleton('core/session')->getReferrerEmail();
            $website_id = Mage::app()->getStore()->getWebsiteId();
            $this->getResponse()->setBody("website: {$website_id}, email: {$email}.");
        } catch (Exception $e) {
            $this->getResponse()->setBody($e->getMessage());
        }
    }

    public function makeCodeAction() 
    {
        try {
            $email = $this->getRequest()->get("email", null);
            $email = urldecode($email);
            $code = Mage::helper('rewardsref/code')->getCode($email);
            $this->getResponse()->setBody($code);
        } catch (Exception $e) {
            $this->getResponse()->setBody($e->getMessage());
        }
    }

    public function preDispatch() 
    {
        parent::preDispatch();
        if (!Mage::helper('rewards/config')->getIsCustomerRewardsActive()) {
            $this->norouteAction();
            return;
        }
    }
}
