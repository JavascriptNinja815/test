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
 * Inactivity Rule Condition Model
 *
 * @category   TBT
 * @package    TBT_Milestone
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Milestone_Model_Rule_Condition_Inactivity extends TBT_Milestone_Model_Rule_Condition
{
    protected $_notification_email = true;
    protected $_notification_frontend = false;
    protected $_notification_backend = false;

    /**
     * Fetch this rule type's reason ID
     * @return string
     */
    public function getReasonId()
    {
        return Mage::helper('rewards/transfer_reason')->getReasonId('milestone_inactivity');
    }

    /**
     * Fetch Milestone Name
     * @return string
     */    
    public function getMilestoneName()
    {
        return Mage::helper('tbtmilestone')->__("Period of Inactivity");
    }

    /**
     * Fetch Milestone Description
     * @return string
     */
    public function getMilestoneDescription()
    {
        return Mage::helper('tbtmilestone')->__("%s day period of inactivity", $this->getThreshold());
    }


    /**
     * This type of condition comes with a prequalifier
     * which would have already ensured that the rule
     * is satisfied for the customer.
     *
     * @see TBT_Milestone_Model_Rule_Condition_Inactivity_Prequalifier::getCollection()
     */    
    public function isSatisfied($customerId)
    {
        return true;
    }

    /**
     * Validate Save
     * @return \TBT_Milestone_Model_Rule_Condition_Inactivity
     * @throws Exception
     */
    public function validateSave()
    {
        if (!$this->getThreshold()) {
            throw new Exception("The milestone threshold is a required field.");
        }

        return $this;
    }
}
