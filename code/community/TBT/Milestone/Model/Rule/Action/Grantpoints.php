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
 */

/**
 * Grandpoints Rule Action Model
 *
 * @category   TBT
 * @package    TBT_Milestone
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Milestone_Model_Rule_Action_Grantpoints extends TBT_Milestone_Model_Rule_Action
{
    /**
     * Execute the Milestone Rule
     * 
     * @param int $customerId
     * @param TBT_Milestone_Model_Rule_Log $milestoneLog
     * @return \TBT_Milestone_Model_Rule_Action_Grantpoints
     */
    public function execute($customerId, $milestoneLog)
    {
        try {
            $transfer = Mage::getModel('rewards/transfer');
            $transfer->setQuantity($this->getPointsAmount())
                ->setCustomerId($customerId)
                ->setComments($this->getTransferComment())
                ->setReasonId($this->getRuleCondition()->getReasonId())
                ->setReferenceId($milestoneLog->getId())
                ->setStatusId(null, $this->getTransferStatus());
            
            $this->setTransfer($transfer);
            $transfer->save();

            /* save this data for the rule log */
            $milestone = array();
            $milestone['condition']['message']           = "Reached a " . $this->getRuleCondition()->getMilestoneDescription();
            $milestone['condition']['reason_id']         = $this->getRuleCondition()->getReasonId();
            $milestone['action']['points']               = $this->getPointsAmount();
            $milestone['action']['transfer_id']          = $transfer->getId();
            $this->getRule()->setMilestoneDetails($milestone);

            $this->notifySuccess();

        } catch (Exception $e){
            Mage::helper('rewards')->logException($e);
        }

        return $this;
    }

    /**
     * Fetch frontend success message
     * @return string
     */
    protected function _getFrontendSuccessMessage()
    {
        return Mage::helper ( 'rewards' )->__("You have earned %s by reaching a %s.", $this->getPointsObject(), $this->getRuleCondition()->getMilestoneDescription());
    }

    /**
     * Fetch backend success message
     * @return string
     */
    protected function _getBackendSuccessMessage()
    {
        return Mage::helper ( 'rewards' )->__("Customer #%s was rewarded %s by reaching a %s.", $this->getCustomerId(), $this->getPointsObject(), $this->getRuleCondition()->getMilestoneDescription());
    }

    /**
     * Fetch email success message
     * @return string
     */
    protected function _getEmailSuccessMessage()
    {
        return Mage::helper ( 'rewards' )->__("You have been rewarded with %s by reaching a %s.", $this->getPointsObject(), $this->getRuleCondition()->getMilestoneDescription());
    }

    /**
     * @return string. Transfer comment loaded from system config
     */
    public function getTransferComment()
    {
        $storeComment = Mage::getStoreConfig("rewards/transferComments/milestone");
        return Mage::helper('tbtmilestone')->__($storeComment, $this->getRuleCondition()->getMilestoneDescription());
    }

    /**
     * @return int. Transfer status loaded from system config
     */
    public function getTransferStatus()
    {
        return Mage::getStoreConfig("rewards/InitialTransferStatus/milestone");
    }

    /**
     * Given a points amount, will return a points model object
     *
     * @param int|null $pointsAmount. Optional. If not supplied, will call $this->getPointsAmount()
     * @return TBT_Rewards_Model_Points
     */
    public function getPointsObject($pointsAmount = null)
    {
        $pointsAmount = !is_null($pointsAmount) ? $pointsAmount : $this->getPointsAmount();
        return Mage::getModel('rewards/points')->setPoints(1, $pointsAmount);
    }

    /**
     * Validate save
     * 
     * @return \TBT_Milestone_Model_Rule_Action_Grantpoints
     * @throws Exception
     */
    public function validateSave()
    {
        if (!$this->getPointsAmount()) {
            throw new Exception("Please specify a points amount in the Actions tab.");
        }

        return $this;
    }
}
