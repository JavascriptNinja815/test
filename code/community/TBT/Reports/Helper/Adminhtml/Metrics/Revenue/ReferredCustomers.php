<?php
/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).

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
 * If you did not receive a copy of the license, please send an email to 
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy 
 * immediately.
 * 
 * @category   [TBT]
 * @package    [TBT_Reports]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Metrics Collection Helper for Revenue From Referred Customers
 *
 * @category   TBT
 * @package    TBT_Reports
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Helper_Adminhtml_Metrics_Revenue_ReferredCustomers
    extends Mage_Adminhtml_Helper_Dashboard_Abstract
{
    /**
     * Total Before Range period
     * @var int|float 
     */
    protected $_totalBeforePeriod;
    
    /**
     * Prepare Collection used for generating Graph
     */
    protected function _initCollection()
    {
        $range = $this->getParam('period');
        $this->_collection = Mage::getResourceModel('tbtreports/order_collection')
            ->onlyCompleteOrders()
            ->onlyOrdersByReferredCustomers(false)
            ->prepareMetricsRevenue($range);
    }
    
    /**
     * Getter for Total Before Range period
     * @return int|float
     */
    public function getTotalBeforePeriod()
    {
        if ($this->_totalBeforePeriod) {
            return $this->_totalBeforePeriod;
        }
        
        $range = $this->getParam('period');
        
        $this->_totalBeforePeriod = Mage::getResourceModel('tbtreports/order_collection')
            ->onlyCompleteOrders()
            ->onlyOrdersByReferredCustomers(false)
            ->getTotalRevenueBeforePeriod($range);
        
        return $this->_totalBeforePeriod;
    }
    
    /**
     * Getter for Total By Date
     * @return int|float
     */
    public function getTotalByDate($startDate = null, $after = true)
    {
        return Mage::getResourceModel('tbtreports/order_collection')
            ->onlyCompleteOrders()
            ->onlyOrdersByReferredCustomers(false)
            ->getTotalRevenueByDate($startDate, $after);
    }
    
    /**
     * Notice message for indexed data
     * @return string|boolean
     */
    public function getNoticeMessage()
    {
        $message = false;
        
        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($this->getParam('period'), null, null);
        
        $firstIndexedOrder = Mage::getResourceModel('tbtreports/order_collection')
            ->onlyCompleteOrders()
            ->onlyOrdersByReferredCustomers(false)
            ->getFirstIndexedOrder();
        
        $dateFrom = $dateRange['from']->toString('Y-MM-dd');
        
        if ($firstIndexedOrder) {
            $firstIndexedOrderCreatedAt = Mage::getModel('core/date')->date('Y-m-d', $firstIndexedOrder['created_at']);

            if (strtotime($firstIndexedOrderCreatedAt) > strtotime($dateFrom)) {
                $orderCreatedAt = date('F j, Y', strtotime($firstIndexedOrderCreatedAt));
                
                $message = Mage::helper('tbtreports')->__(
                    "Referred customers revenue data has been available after %s only.", $orderCreatedAt
                );
            }
        } else {
            $notificationLink = Mage::getBlockSingleton('index/adminhtml_notifications')
                ->getManageUrl();

            $message = Mage::helper('tbtreports')->__(
                "We don't have enough referred customers revenue data available just yet. Check again later."
            );

            $message .= '<br/>' . Mage::helper('tbtreports')->__(
                "Please make sure that you've rebuilt any missing %s indexes",
                "MageRewards"
            );
            $message .= ': ' . '<a href="' . $notificationLink . '">'
                . Mage::helper('tbtreports')->__('Index Management') . '</a>';
        }
        
        return $message;
    }
}