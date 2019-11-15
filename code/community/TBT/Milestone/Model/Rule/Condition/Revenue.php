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
 * Revenue Rule Condition Model
 *
 * @category   TBT
 * @package    TBT_Milestone
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Milestone_Model_Rule_Condition_Revenue extends TBT_Milestone_Model_Rule_Condition
{
    /**
     * Should we email the customer if milestone is reached?
     * We overwrite the parent here so that we also send email notifications in case a payment is used which also
     * automatically creates the invoice when the order is placed.
     * 
     * @var boolean. 
     */
    protected $_notification_email = true;

    /**
     * Fetch this rule type's reason ID
     * @return string
     */
    public function getReasonId()
    {
        return Mage::helper('rewards/transfer_reason')->getReasonId('milestone_revenue');
    }
    
    /**
     * Fetch Milestone Name
     * @return string
     */
    public function getMilestoneName()
    {
        return Mage::helper('tbtmilestone')->__("Revenue Milestone");
    }

    /**
     * Fetch Milestone Description
     * @return string
     */
    public function getMilestoneDescription()
    {
        $threshold = Mage::app()->getStore()->getBaseCurrency()->format($this->getThreshold(), array(), false);
        return Mage::helper('tbtmilestone')->__("milestone for reaching %s in revenue", $threshold);
    }

    /**
     * Are conditions satisfied for this rule?
     * 
     * @param int $customerId
     * @return bool
     */
    public function isSatisfied($customerId)
    {
        $storeIds = $this->_getHelper()->getStoreIdsFromWebsites($this->getRule()->getWebsiteIds());

        $invoiceCollectionBeforeStart = Mage::getResourceModel('sales/order_invoice_collection')
            ->addFieldToFilter('main_table.store_id', array('in' => $storeIds))
            ->addFieldToFilter('main_table.state', Mage_Sales_Model_Order_Invoice::STATE_PAID)
            ->addFieldToFilter('main_table.created_at', array('lt' => $this->getFromDate()));
        $invoiceCollectionBeforeStart->getSelect()->join(
            array('order_table' => Mage::getSingleton('core/resource')->getTableName('sales/order')),
            "main_table.order_id = order_table.entity_id",
            array('order_table.customer_id')
        );
        $invoiceCollectionBeforeStart->addFieldToFilter('order_table.customer_id', $customerId);

        $invoiceCollectionAfterStart = Mage::getResourceModel('sales/order_invoice_collection')
            ->addFieldToFilter('main_table.store_id', array('in' => $storeIds))
            ->addFieldToFilter('main_table.state', Mage_Sales_Model_Order_Invoice::STATE_PAID)
            ->addFieldToFilter('main_table.created_at', array('gteq' => $this->getFromDate()));
        $invoiceCollectionAfterStart->getSelect()->join(
            array('order_table' => Mage::getSingleton('core/resource')->getTableName('sales/order')),
            "main_table.order_id = order_table.entity_id",
            array('order_table.customer_id')
        );
        $invoiceCollectionAfterStart->addFieldToFilter('order_table.customer_id', $customerId);

        if ($this->getToDate()) {
            $invoiceCollectionAfterStart->addFieldToFilter('main_table.created_at', array('lt' => $this->getToDate()));
        }

        $totalRevenueBeforeStart = $this->_fetchRevenue($invoiceCollectionBeforeStart);
        $totalRevenueAfterStart = $this->_fetchRevenue($invoiceCollectionAfterStart);

        $totalRevenue = $totalRevenueBeforeStart + $totalRevenueAfterStart;

        // Convert currency amounts to integers to circumvent any ugly floating-point headaches.
        $totalRevenueBeforeStart = (int) round($totalRevenueBeforeStart * 4, 0);
        $totalRevenue = (int) round($totalRevenue * 4, 0);
        $threshold = (int) round($this->getThreshold() * 4, 0);

        return $totalRevenueBeforeStart < $threshold && $totalRevenue >= $threshold;
    }

    /**
     * Validate Save
     * @return \TBT_Milestone_Model_Rule_Condition_Revenue
     * @throws Exception
     */
    public function validateSave()
    {
        if (!$this->getThreshold()) {
            throw new Exception("Revenue amount is a required field.");
        }

        return $this;
    }

    /**
     * Fetch the total revenue for the customer
     * 
     * @param Mage_Sales_Model_Resource_Order_Invoice_Collection $collection
     * @return string
     */
    protected function _fetchRevenue($collection)
    {
        $collection->getSelect()->reset(Zend_Db_Select::COLUMNS);
        $collection->getSelect()->group('order_table.customer_id');
        $collection->addExpressionFieldToSelect('total_revenue', "SUM(main_table.base_grand_total)", array());

        return $collection->getFirstItem()->getData('total_revenue');
    }
}
