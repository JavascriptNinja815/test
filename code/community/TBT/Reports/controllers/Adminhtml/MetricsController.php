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

require_once(Mage::getModuleDir('controllers', 'TBT_Reports') . DS . 'AjaxController.php');

/**
 * Reports Metrics Controller
 *
 * @category   TBT
 * @package    TBT_Reports
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Adminhtml_MetricsController extends TBT_Reports_AjaxController
{
    /**
     * Revenue Report
     */
    public function revenueAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    /**
     * Signups Report
     */
    public function signupsAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    /**
     * Points Activity Report
     */
    public function activityAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    /**
     * Revenue Members Report
     */
    public function revenueMembersAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    /**
     * Revenue Referred Customers Report
     */
    public function revenueReferredAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    /**
     * Orders Report
     */
    public function ordersAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    /**
     * New Signups Report
     */
    public function newSignupsAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    /**
     * Referred Signups Report
     */
    public function referredSignupsAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    /**
     * Points Earned Report
     */
    public function pointsEarnedAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
        /**
     * Points Earned Report
     */
    public function earningDistributionAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    /**
     * Points Spent Report
     */
    public function pointsSpentAction()
    {
        $this->loadLayout(false);
        $this->renderLayout();
    }
    
    /**
     * Update Metrics Report based on range
     */
    public function updateMetricsAction()
    {
        $reportCode = $this->getRequest()->getParam('report_code');
        
        $this->loadLayout('adminhtml_update_metrics_' . $reportCode . '_handle');
        $this->renderLayout();
    }
    
    /**
     * Metric Data by Report Code
     * @return string
     */
    public function metricDataAction()
    {
        $reportCode = $this->getRequest()->getParam('report_code');
        $startDate = $this->getRequest()->getParam('start_date');
        
        $reportHelper = false;
        
        $totalBefore = 0;
        $grandTotal = 0;
        
        switch($reportCode) {
            case 'revenue_loyalty':
                $reportHelper = Mage::helper('tbtreports/adminhtml_metrics_revenue_loyalty');
                break;
            case 'revenue_referred_customers':
                $reportHelper = Mage::helper('tbtreports/adminhtml_metrics_revenue_referredCustomers');
                break;
            case 'orders_repeat_purchase':
                $reportHelper = Mage::helper('tbtreports/adminhtml_metrics_orders_repeatPurchase');
                break;
            case 'signups_new':
                $reportHelper = Mage::helper('tbtreports/adminhtml_metrics_signups_new');
                break;
            case 'signups_referral':
                $reportHelper = Mage::helper('tbtreports/adminhtml_metrics_signups_referral');
                break;
            case 'activity_earned':
                $reportHelper = Mage::helper('tbtreports/adminhtml_metrics_activity_earned');
                break;
            case 'activity_spent':
                $reportHelper = Mage::helper('tbtreports/adminhtml_metrics_activity_spent');
                break;
        }
        
        if ($reportHelper !== false) {
            $totalBefore = ($startDate) ? $reportHelper->getTotalByDate($startDate, false) : 0;
            $grandTotal = $reportHelper->getTotalByDate();
        }
        
        $metricData = array(
            'report_code' => $reportCode,
            'start_date' => $startDate,
            'total_before_date' => $totalBefore,
            'total' => $grandTotal
        );
        
        return $this->jsonResponse($metricData);
    }
}