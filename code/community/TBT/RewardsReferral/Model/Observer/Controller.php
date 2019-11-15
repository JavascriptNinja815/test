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
 * Controller observer class used for referral events related to controllers
 * @package     TBT_RewardsReferral
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Model_Observer_Controller
{
    /**
     * Bind Referrer On Controller Action PreDispatch
     * @param Varien_Event_Observer $observer
     * @see 'controller_action_predispatch'
     * @return \TBT_RewardsReferral_Model_Observer_Controller
     * @throws Exception
     */
    public function bindReferrerOnActionPreDispatch(Varien_Event_Observer $observer)
    {
        $code = urldecode(Mage::app()->getRequest()->get("st-code", null));
        $referrerId = urldecode(Mage::app()->getRequest()->get("st-id", null));

        if (!$code && !$referrerId) {
            return $this;
        }

        $codeHelper = Mage::helper('rewardsref/code');

        if (!empty($code)) {
            $email = Mage::helper('rewardsref/code')->getEmail($code);
        } elseif (!empty($referrerId)) {
            $email = Mage::getModel('rewards/customer')->load($referrerId)->getEmail();
        } else {
            return $this;
        }

        $codeHelper->setReferrer($email);

        return $this;
    }
}