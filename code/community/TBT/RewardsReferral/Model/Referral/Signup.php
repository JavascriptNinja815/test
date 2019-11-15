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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Referral Model
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsReferral_Model_Referral_Signup extends TBT_RewardsReferral_Model_Referral_Abstract
{
    const REFERRAL_STATUS = 2;

    /**
     * @deprecated  user self::REFERRAL_STATUS
     */
    const STATUS_REFERRAL_SIGNED_UP = 1;

    public function getReferralStatusId()
    {
        return self::REFERRAL_STATUS;
    }

    public function getReferralTransferMessage($newCustomer)
    {
        return Mage::getStoreConfig('rewards/transferComments/referralSignup');
    }

    public function getTotalReferralPoints()
    {
        $applicable_rules = Mage::getSingleton('rewardsref/validator')
            ->getApplicableRules(TBT_RewardsReferral_Model_Special_Signup::ACTION_REFERRAL_SIGNUP);
        $points = Mage::getModel('rewards/points');
        foreach ($applicable_rules as $arr) {
            $points->add($arr);
        }

        return $points;
    }

    public function getTransferReasonId()
    {
        return Mage::helper('rewards/transfer_reason')->getReasonId('referral_signup');
    }

    /**
     * Referral Signup trigger
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return TBT_RewardsReferral_Model_Referral_Abstract
     */
    public function triggerEvent(Mage_Customer_Model_Customer $customer)
    {
        $this->initReferral($customer);
        
        if (!$this->getReferralParentId()) {
            Mage::helper('rewardsref')->initateSessionReferral($customer);
            $this->loadByEmail($customer->getEmail());
            if (!$this->getReferralParentId()) {
                return $this;
            }
        }

        if (!$this->isValidParentWebsite()) {
            return $this;
        }
        
        // update referral status
        $this->setReferralChildId($customer->getId());
        $this->_updateReferralStatus($this->getReferralStatusId());
        $this->save();
        
        $points = $this->getTotalReferralPoints();
        if ($points->isEmpty()) {
            return $this;
        }

        $applicableRules = Mage::getSingleton('rewardsref/validator')
            ->getApplicableRules(TBT_RewardsReferral_Model_Special_Signup::ACTION_REFERRAL_SIGNUP);
        
        try {
            foreach ($applicableRules as $rule) {
                $pointsAmount = $rule->getPointsAmount();
                $transfer       = Mage::getModel('rewardsref/transfer');
                
                $transferEffectiveStart = null;
                $transferStatus = Mage::getStoreConfig('rewards/InitialTransferStatus/ReferralSignup');
                
                // Get on-hold initial status override
                if ($rule->getOnholdDuration() > 0) {
                    $transferEffectiveStart = date('Y-m-d H:i:s', strtotime("+{$rule->getOnholdDuration()} days"));
                    $transferStatus = TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_TIME;
                }
                
                $transfer->create(
                    $pointsAmount,
                    $this->getReferralParentId(),
                    $customer->getId(),
                    $this->getReferralTransferMessage($customer),
                    $this->getTransferReasonId(),
                    $transferStatus,
                    null,
                    $transferEffectiveStart
                );
            }

            // send affiliate an email of the transaction
            $affiliate = $this->getParentCustomer();
            if ($affiliate->getRewardsrefNotifyOnReferral()) {
                $msg = $this->getReferralTransferMessage($customer);
                $this->sendConfirmation($affiliate, $customer->getEmail(), $customer->getName(), $msg, (string)$points);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

}
