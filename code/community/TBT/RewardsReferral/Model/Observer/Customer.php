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
 * Referral events related to Customers
 * @package     TBT_RewardsReferral
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Model_Observer_Customer
{
    /**
     * Bind Referrer to New Created Admin Customer
     * @param Varien_Event_Observer $observer
     * @return \TBT_RewardsReferral_Model_Observer_Customer
     */
    public function saveAdminReferralOnCustomerSaveAfter(Varien_Event_Observer $observer)
    {
        $customer = $observer->getCustomer();

        if (!$customer || !$customer->getId()) {
            return $this;
        }

        $referrerCustomerId = Mage::app()->getRequest()->getParam('referrer_customer_id');

        if (!$referrerCustomerId) {
            $refByChildId = Mage::getModel('rewardsref/referral')
                ->loadByReferralId($customer->getId());

            if ($refByChildId && $refByChildId->getId()) {
                try {
                    $refByChildId->delete();
                } catch (Exception $exc) {
                    Mage::getSingleton('adminhtml/session')
                        ->addError('An unexpected error occured while trying to unbind referrer for this customer. Please try again later!');
                    }
            }

            return $this;
        }

        $referrer = Mage::getModel('rewards/customer')->load($referrerCustomerId);

        $referralService = Mage::getModel('rewardsref/service_referral')
            ->setReferrerCustomer($referrer)
            ->setReferralCustomer($customer);

        try {
            $referralService->bind();
        } catch (Exception $exc) {
            Mage::getSingleton('adminhtml/session')
                ->addError('Referrer is not valid for this customer!');
        }

        return $this;
    }

    /**
     * Bind Referral to Referrer
     * Usage: Mage::dispatchEvent('rewardsref_bind_referral', array('referrer_customer' => $referrerCustomer, 'referral_customer' => $referralCustomer));
     *
     * @see `rewardsref_bind_referral` event
     * @param Varien_Event_Observer $observer
     * @return \TBT_RewardsReferral_Model_Observer_Customer
     */
    public function bindReferral(Varien_Event_Observer $observer)
    {
        $referrerCustomer = $observer->getReferrerCustomer();
        $referralCustomer = $observer->getReferralCustomer();
        $triggerPoints = (is_bool($observer->getTriggerPoints())) ? $observer->getTriggerPoints() : true;
        $printError = (is_bool($observer->getPrintError())) ? $observer->getPrintError() : false;

        $referralService = Mage::getModel('rewardsref/service_referral')
            ->setReferrerCustomer($referrerCustomer)
            ->setReferralCustomer($referralCustomer);

        try {
            $referralService->bind($triggerPoints);
        } catch (Exception $exc) {
            Mage::logException($exc);

            if ($printError) {
                Mage::getSingleton('core/session')
                    ->addError('Referrer is not valid for this customer!');
            }
        }

        return $this;
    }
}