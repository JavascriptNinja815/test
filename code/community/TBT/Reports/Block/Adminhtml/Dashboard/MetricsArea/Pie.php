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
 * Pie Metrics Report
 *
 * @category   TBT
 * @package    TBT_Reports
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
abstract class TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Pie
    extends Mage_Adminhtml_Block_Template
{
    /**
     * Report label cache
     * @var string 
     */
    protected $_reportLabel;
    
    /**
     * Data Helper.
     *
     * @var TBT_Rewards_Helper_Metrics_Chart_Abstract
     **/
    protected $_helper;
    
    /**
     * General Metrics Cache Tag
     * @var string 
     */
    protected $_generalCacheTag = 'REWARDS_DASHBOARD_METRICS';
    
    /**
     * Number of seconds for Cache Lifetime
     * @var int 
     */
    protected $_generalCacheLifetime = 60;
    
    /**
     * Main Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('tbtreports/dashboard/metricsarea/pie.phtml');
    }
    
    /**
     * Setter for Report Label
     * @param string $label
     * @return \TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Graph
     */
    public function setReportLabel($label)
    {
        $this->_reportLabel = $label;
        
        return $this;
    }
    
    /**
     * Getter for Report Label
     * @return string
     */
    public function getReportLabel()
    {
        return Mage::helper('tbtreports')->__($this->_reportLabel);
    }
    
    /**
     * Metrics Update Url for range change
     * @return string
     */
    protected function getMetricsUpdateUrl()
    {
        $isSecure = (bool) Mage::app()->getStore()->isCurrentlySecure();

        return Mage::getUrl('*/adminhtml_metrics/updateMetrics', array('_secure' => $isSecure));
    }
    
    /**
     * Validates if current request period is the same with select option value
     * @param string $period
     * @return boolean
     */
    protected function isSelectedPeriod($period)
    {
        $requestPeriod = $this->getRequest()->getParam('period', null);
        
        if ($requestPeriod === $period) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Setter for the data helper to be used to get the data for the chart.
     *
     * @param string $helperClass
     * @return  TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Pie
     */
    public function setDataHelper($helperClass)
    {
        $this->_helper = $this->helper($helperClass);
        return $this;
    }

    /**
     * Getter for the chart data helper class.
     *
     * @return TBT_Rewards_Helper_Metrics_Chart_Abstract
     */
    public function getDataHelper()
    {
        return $this->_helper;
    }

    /**
     * Sets the filter data and prepares data for the chart.
     *
     * @return [type] [description]
     */
    protected function _beforeToHtml()
    {
        $this->_prepareData();

        return parent::_beforeToHtml();
    }

    /**
     * Prepares data for the chart. Overwrite in child classes as needed.
     *
     * @return TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Pie
     */
    protected function _prepareData()
    {
        $availablePeriods = array_keys(Mage::helper('tbtreports/adminhtml_metrics_data')->getDatePeriods());
        $period = $this->getRequest()->getParam('period');

        $this->getDataHelper()->setParam('period',
            ($period && in_array($period, $availablePeriods)) ? $period : '30d'
        );
        
        $this->getDataHelper()->setParam(
            'period_type',
            $this->_getPeriodType($this->getParam('period'))
        );
        
        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($this->getDataHelper()->getParam('period'), null, null);
        
        $this->getDataHelper()->setParam('from', $dateRange['from']->toString('Y-MM-dd'));
        $this->getDataHelper()->setParam('to', $dateRange['to']->toString('Y-MM-dd'));
        
        return $this;
    }
    
    /**
     * Getter for Period Type
     * @param string $period
     * @return string
     */
    protected function _getPeriodType($period)
    {
        $periodType = 'day';
        
        return $periodType;
    }
    
    /**
     * Retrieve format needed for chart's X axis (time axis) based on the 'period_type' filter value.
     *
     * @return string
     */
    public function getChartDateFormat()
    {
        $format = '%b %d, %Y';
        if (!$this->getFilterData()) {
            return $format;
        }

        $filterData = $this->getFilterData()->getData();
        if (isset($filterData['period_type'])) {
            if ($filterData['period_type'] == 'month') {
                $format = '%b %Y';
            } elseif ($filterData['period_type'] == 'year') {
                $format = '%Y';
            }
        }

        return $format;
    }

    /**
     * Returns the Y axis format as defined in the data helper class.
     *
     * @return string
     */
    public function getChartYFormat()
    {
        return $this->getDataHelper()->getYAxisFormat();
    }

    /**
     * Returns any symbol that should preceed the Y axis values. For example "$".
     * Should be defined in the helper class.
     *
     * @return string
     */
    public function getPreSymbol()
    {
        return $this->getDataHelper()->getPreSymbol();
    }

    /**
     * Returns any symbol that should succed the Y axis values. For example "$" for currency.
     * Should be defined in the helper class.
     *
     * @return string
     */
    public function getPostSymbol()
    {
        return $this->getDataHelper()->getPostSymbol();
    }

    /**
     * Returns the data for the chart, JSON encoded, ready for displaying.
     *
     * @return string
     */
    public function getChartData()
    {
        $data = $this->getDataHelper()->getAllSeries();

        return Zend_Json::encode($data);
    }

    /**
     * Checks if there is data for the pie chart
     * @return boolean
     */
    protected function hasChartData()
    {
        $data = $this->getDataHelper()->getAllSeries();

        if (is_array($data) && count($data) > 0) {
            return true;
        }

        return false;
    }

    protected function getNoticeMessage()
    {
        $message = Mage::helper('tbtreports')
            ->__("We don't have enough earning distribution data available just yet.");
        $message .= '<br/>';
        $message .= Mage::helper('tbtreports')
            ->__("Change the date range or check again later.");

        return $message;
    }
}