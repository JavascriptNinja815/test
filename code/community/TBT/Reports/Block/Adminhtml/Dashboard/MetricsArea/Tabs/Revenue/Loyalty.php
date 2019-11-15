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
 * Revenue Loyalty Members Metrics Reports
 *
 * @category   TBT
 * @package    TBT_Reports
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Tabs_Revenue_Loyalty
    extends TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Graph
{   
    /**
     * Main Constructor
     */
    public function _construct()
    {
        parent::_construct();
        
        $metricInfo = <<<METRICINFO
            {$this->__('Consists of revenue produced by any customer who')}:<br/><br/>
            <ul>
                <li>{$this->__('has ever spent points.')}</li>
                <li>{$this->__('has earned points from at least 2 orders.')}</li>
                <li>{$this->__('has earned points from at least one order and one other action (ie: reviews, social media shares).')}</li>
                <li>{$this->__('has ever transitioned to a new customer tier as a result of reaching a milestone.')}</li>
                <li>{$this->__('has been referred by someone else.')}</li>
            </ul><br/>
            {$this->__('Such customers are considered to be active members of your loyalty program.')}
METRICINFO;
        $this->setMetricInfo(str_replace('\n', '', trim($metricInfo)));
    }
    
    /**
     * Prepare chart data
     *
     * @return void
     */
    protected function _prepareData()
    {
        $this->setDataHelperName('tbtreports/adminhtml_metrics_revenue_loyalty');
        $this->getDataHelper()->setParam('store', $this->getRequest()->getParam('store'));
        $this->getDataHelper()->setParam('website', $this->getRequest()->getParam('website'));
        $this->getDataHelper()->setParam('group', $this->getRequest()->getParam('group'));

        $this->setDataRows('revenue');
        $this->_axisMaps = array(
            'x' => 'range',
            'y' => 'revenue'
        );
        
        parent::_prepareData();
        
        $this->addData(
            array(
                'cache_lifetime' => $this->_generalCacheLifetime,
                'cache_tags' => array(
                    $this->_generalCacheTag,
                    TBT_Rewards_Model_Sales_Order::CACHE_TAG
                ),
                'cache_key' => $this->_generalCacheTag
                    . '_REVENUE_LOYALTY'
                    . '_' . $this->getDataHelper()->getParam('period')
            )
        );
    }
    
    /**
     * Set loyalty orders grid URL before the block is rendered
     * @return string
     */
    protected function _toHtml()
    {
        $this->addAdditionalLink(
            'loyalty-orders-grid', 
            $this->getUrl('adminhtml/adminhtml_loyalty_order', array('report_code' => $this->getReportCode())),
            $this->__('View Orders from Loyalty Members')
        );
        
        $this->addAdditionalLink(
            'loyalty-purchase-rate-grid', 
            $this->getUrl('adminhtml/adminhtml_loyalty_purchaseRate', array('report_code' => $this->getReportCode())),
            $this->__('View Purchasing Frequencies')
        );
        
        return parent::_toHtml();
    }
}

