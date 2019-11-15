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
 * Earned Rule Condition Model
 *
 * @category   TBT
 * @package    TBT_Milestone
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Milestone_Model_Rule_Condition_Earned extends TBT_Milestone_Model_Rule_Condition
{
    /**
     * Fetch Milestone Name
     * @return string
     */
    public function getMilestoneName()
    {
        return Mage::helper('tbtmilestone')->__("Points Earned Milestone");
    }
    
    /**
     * Fetch this rule type's reason ID
     * @return string
     */
    public function getReasonId()
    {
        return Mage::helper('rewards/transfer_reason')->getReasonId('milestone_earned');
    }

    /**
     * Fetch Milestone Description
     * @return string
     */
    public function getMilestoneDescription()
    {
        $threshold = (string) Mage::getModel('rewards/points')->setPoints(1, $this->getThreshold());
        return Mage::helper('tbtmilestone')->__("milestone for having earned a total of %s", $threshold);
    }

    /**
     * Are conditions satisfied for this rule?
     * 
     * @param int $customerId
     * @return bool
     */
    public function isSatisfied($customerId)
    {
        $statuses = array(TBT_Rewards_Model_Transfer_Status::STATUS_APPROVED);
        if (Mage::helper('tbtmilestone/config')->doIncludePendingTransfers()) {
            $statuses = array_merge($statuses, array(
                TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_EVENT,
                TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_APPROVAL,
                TBT_Rewards_Model_Transfer_Status::STATUS_PENDING_TIME
            ));
        }

        $collection = Mage::getResourceModel('rewards/transfer_collection')
            ->addFieldToFilter('main_table.customer_id', $customerId)
            ->addFieldToFilter('main_table.status_id', array('in' => $statuses))
            ->addFieldToFilter('main_table.created_at', array('gteq' => $this->getFromDate()));
        $collection->getSelect()->join(
            array('customer_table' => $this->_getResource()->getTableName('customer/entity')),
            "customer_table.entity_id = main_table.customer_id",
            array()
        );
        $collection->addFieldToFilter('customer_table.website_id', array('in' => $this->getRule()->getWebsiteIds()));
        $collection->addFieldToFilter('customer_table.group_id', array('in' => $this->getRule()->getCustomerGroupIds()));

        if ($this->getToDate()) {
            $collection->addFieldToFilter('main_table.created_at', array('lt' => $this->getToDate()));
        }

        $totalPointsEarned = $this->_fetchPoints($collection);

        return $totalPointsEarned >= $this->getThreshold();
    }

    /**
     * Validate Save
     * 
     * @return \TBT_Milestone_Model_Rule_Condition_Earned
     * @throws Exception
     */
    public function validateSave()
    {
        if (!$this->getThreshold()) {
            throw new Exception("Earned Points is a required field.");
        }

        return $this;
    }

    /**
     * Fetch the points sum for the customer
     * 
     * @param TBT_Rewards_Model_Mysql4_Transfer_Collection $collection
     * @return string
     */
    protected function _fetchPoints($collection)
    {
        $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS);

        $collection->getSelect()
            ->group('main_table.customer_id');

        $collection->addExpressionFieldToSelect('total_points', "SUM(main_table.quantity)", array());

        return $collection->getFirstItem()->getData('total_points');
    }

    /**
     * @return Mage_Core_Model_Resource
     */
    protected function _getResource()
    {
        return Mage::getSingleton('core/resource');
    }
}
