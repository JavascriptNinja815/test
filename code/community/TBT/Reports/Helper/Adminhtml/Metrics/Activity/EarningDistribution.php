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
 * Metrics Collection Helper for Points Activity Earning Distribution
 *
 * @category   TBT
 * @package    TBT_Reports
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Helper_Adminhtml_Metrics_Activity_EarningDistribution
    extends TBT_Reports_Helper_Adminhtml_Metrics_Chart_Abstract
{
    /**
     * Initializes series of data.
     *
     * @return TBT_Rewards_Helper_Metrics_Earnings
     */
    protected function _initSeries()
    {
        $this->_initCollection();
        $results = $this->_collection
            ->load()
            ->getData();

        $this->_prepareSeries($results);

        return $this;
    }

    /**
     * Initialize Collection for Earning Distribution
     * @return \TBT_Reports_Helper_Adminhtml_Metrics_Activity_EarningDistribution
     */
    protected function _initCollection()
    {
        if (!is_null($this->_collection)) {
            return $this;
        }

        $period         = $this->getParam('period_type');
        $from           = $this->getParam('from');
        $to             = $this->getParam('to') . ' 23:59:59';
        $transferStatus = $this->getParam('transfer_statuses');
        $storeIds       = $this->_getStoreIds();

        $this->_collection = Mage::getResourceSingleton('rewards/metrics_earnings_collection')
            ->prepareSummary($period, $storeIds, $from, $to, $transferStatus);

        return $this;
    }

    /**
     * Earning Distribution Series
     * @param array $series
     * @return \TBT_Reports_Helper_Adminhtml_Metrics_Activity_EarningDistribution
     */
    protected function _prepareSeries($series = array())
    {
        if (is_null($series)) {
            return $this;
        }

        foreach ($series as $key => &$value) {
            $value['reason_id'] = $this->getReasonCaption($value['reason_id']);
        }

        $this->setAllSeries($series);

        return $this;
    }

    /**
     * Fetch Reason Label
     * 
     * @param  string $reasonId
     * @return string
     */
    public function getReasonCaption($reasonId)
    {
        return Mage::helper('rewards/transfer_reason')->getReasonLabel($reasonId);
    }
}
