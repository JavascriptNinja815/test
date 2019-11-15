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
 * Orders Rule Condition Model
 *
 * @category   TBT
 * @package    TBT_Milestone
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Milestone_Model_Rule_Condition_Orders extends TBT_Milestone_Model_Rule_Condition
{
    /**
     * Fetch this rule type's reason ID
     * @return string
     */
    public function getReasonId()
    {
        return Mage::helper('rewards/transfer_reason')->getReasonId('milestone_order');
    }
    
    /**
     * Fetch Milestone Name
     * @return string
     */
    public function getMilestoneName()
    {
        return Mage::helper('tbtmilestone')->__("Number of Orders Milestone");
    }

    /**
     * Fetch Milestone Description
     * @return string
     */
    public function getMilestoneDescription()
    {
        if (intval($this->getThreshold() == 1)){
            return Mage::helper('tbtmilestone')->__("milestone for placing %s order", $this->getThreshold());

        }  else {
            return Mage::helper('tbtmilestone')->__("milestone for placing %s orders", $this->getThreshold());
        }
    }

    /**
     * Are conditions satisfied for this rule?
     * 
     * @param int $customerId
     * @return bool
     */
    public function isSatisfied($customerId)
    {
        $threshold = intval($this->getThreshold());
        $fromDate = $this->getFromDate();
        $toDate = $this->getToDate();
        $storeIds = $this->_getHelper()->getStoreIdsFromWebsites($this->getRule()->getWebsiteIds());

        $ordersBeforeStartDate = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('main_table.customer_id', $customerId)
            ->addFieldToFilter('main_table.store_id',   array("in" => $storeIds))
            ->addFieldToFilter('main_table.created_at', array("lt" => $fromDate));


        $ordersAfterStartDate = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('main_table.customer_id', $customerId)
            ->addFieldToFilter('main_table.store_id',   array("in" => $storeIds))
            ->addFieldToFilter('main_table.created_at', array("gteq" => $fromDate));
        
        if (!empty($toDate)){
            $ordersAfterStartDate->addFieldToFilter("main_table.created_at", array("lt" => $toDate));
        }

        $this->_addCountingConstraints($ordersBeforeStartDate);
        $this->_addCountingConstraints($ordersAfterStartDate);

        $countBeforeStartDate = $ordersBeforeStartDate->getSize();
        $countAfterStartDate = $ordersAfterStartDate->getSize();
        $countTotal = $countBeforeStartDate + $countAfterStartDate;

        return ($countBeforeStartDate < $threshold && $countTotal >= $threshold);
    }

    /**
     * Accepts a Sales Order Collection and places count restrictions on it based on config settings
     * Aka. What should we count as an order?
     *
     * @param Mage_Sales_Model_Mysql4_Order_Collection $collection
     * @return Mage_Sales_Model_Mysql4_Order_Collection $collection. The same collection, just modified.
     */
    protected function _addCountingConstraints(&$collection)
    {
        $orderCountTrigger = $this->_getHelper('config')->getOrdersTrigger();
        switch ($orderCountTrigger){
            case "payment":
                /* Count everything that has an invoice */
                $collection->getSelect()->join(
                    array("invoice" => $this->_getInvoiceTableName()),
                    "main_table.entity_id = invoice.order_id"
                );
                break;
            case "shipment":
                /* Count everything that has a shipment */
                $collection->getSelect()->join(
                    array("shipment" => $this->_getShipmentTableName()),
                    "main_table.entity_id = shipment.order_id"
                );
                break;
            case "create":
                /* Nothing specific */
            default:
                break;
        }

        /* Make sure we're always looking at orders which are not canceled */
        $collection->addFieldToFilter('main_table.state',
            array("nin" => array(
                Mage_Sales_Model_Order::STATE_CANCELED
            ))
        );

        return $collection;
    }

    /**
     * Get the table name for the sales/invoice table
     * @return string
     */
    protected function _getInvoiceTableName()
    {
        if (!isset($this->_invoiceTable)){
            $this->_invoiceTable = Mage::getSingleton('core/resource')->getTableName('sales/invoice');
        }

        return $this->_invoiceTable;
    }

    /**
     * Get the table name for the sales/shipment table
     * @return string
     */
    protected function _getShipmentTableName()
    {
        if (!isset($this->_shipmentTable)){
            $this->_shipmentTable = Mage::getSingleton('core/resource')->getTableName('sales/shipment');
        }

        return $this->_shipmentTable;
    }

    /**
     * Validate Save
     * @return \TBT_Milestone_Model_Rule_Condition_Orders
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
