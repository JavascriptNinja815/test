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
 * Metrics Collection Helper for Orders Repeat Purchase Count
 *
 * @category   TBT
 * @package    TBT_Reports
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Helper_Adminhtml_Metrics_Orders_RepeatPurchase
    extends TBT_Reports_Helper_Adminhtml_Metrics_Chart_Abstract
{
    protected $data;
    
    /**
     * Initializes series of data.
     * @return TBT_Reports_Helper_Adminhtml_Metrics_Orders_RepeatPurchase
     */
    protected function _initSeries()
    {
        if (!is_null($this->data)) {
            return $this;
        }
        
        $period = $this->getParam('period');
        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();
        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($period, 0, 0);
        
        $readAdapter = Mage::getSingleton('core/resource')->getConnection('core_read');
        $ordersCountSubquery = $readAdapter->select()
            ->from(
                array(
                    'orders' => Mage::getSingleton('core/resource')->getTableName('sales_flat_order_grid')
                ),
                array(
                    'orders_count' => new Zend_Db_Expr('COUNT(*)'),
                    'customer_id' => 'customer_id'
                )
            )
            ->where('orders.customer_id IS NOT NULL')
            ->where('orders.status = ?', 'complete')
            ->where("DATE_FORMAT(DATE_ADD(orders.created_at, INTERVAL $gmtOffset SECOND), '%Y-%m-%d') >= '" . $dateRange['from']->toString('Y-MM-dd') . "'")
            ->group('orders.customer_id');
        
        $data = $readAdapter->select()
            ->from(
                array(
                    'orders_data' => new Zend_Db_Expr('('. $ordersCountSubquery .')')
                ),
                array(
                    "SUM(CASE WHEN orders_count = 1 THEN 1 ELSE 0 END) as Customers who have placed only one order",
                    "SUM(CASE WHEN orders_count > 1 THEN 1 ELSE 0 END) as Customers who have placed more than one order"
                )
            );
        
        $results = $readAdapter->fetchRow($data);
        if ($results) {
            $series = array();
            foreach ($results as $label => $count) {
                $series[] = array('label' => $label, 'count' => $count);
            }
        
            $this->data = $series;
            $this->setAllSeries($series);
        }
        
        return $this;
    }
}

