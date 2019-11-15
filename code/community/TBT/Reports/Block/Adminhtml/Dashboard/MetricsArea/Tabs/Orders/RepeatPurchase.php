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
 * Number of Customers for Repeat Purchase Orders Metrics Reports
 *
 * @category   TBT
 * @package    TBT_Reports
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Tabs_Orders_RepeatPurchase
    extends TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Pie
{
    
    /**
     * Main Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('tbtreports/dashboard/metricsarea/repeat_purchases_pie.phtml');
        $this->setMetricInfo('Distribution of customers who have placed one order and those who have placed multiple orders.');
    }
    
    /**
     * Prepare chart data
     *
     * @return void
     */
    protected function _prepareData()
    {
        $this->setDataHelper('tbtreports/adminhtml_metrics_orders_repeatPurchase');

        parent::_prepareData();
        
        $this->addData(
            array(
                'cache_lifetime' => $this->_generalCacheLifetime,
                'cache_tags' => array(
                    $this->_generalCacheTag,
                    TBT_Rewards_Model_Sales_Order::CACHE_TAG
                ),
                'cache_key' => $this->_generalCacheTag
                    . '_ORDERS_REPEAT_PURCHASE'
                    . '_' . $this->getDataHelper()->getParam('period')
            )
        );
    }
}

