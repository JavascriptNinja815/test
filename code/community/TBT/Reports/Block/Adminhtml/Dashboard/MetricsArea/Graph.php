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
 * Metrics Dashboard Graph
 *
 * @category   TBT
 * @package    TBT_Reports
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Graph
    extends Mage_Adminhtml_Block_Dashboard_Graph
{
    /**
     * Cummulative value
     * @var int|float 
     */
    protected $_cummulativeValue = 0;
    
    /**
     * Chart Url
     * @var string 
     */
    protected $_chartUrl = '';
    
    /**
     * Computed date from string from range
     * @var string 
     */
    protected $_rangeDateFrom;
    
    /**
     * Computed date to string from range
     * @var string
     */
    protected $_rangeDateTo;
    
    /**
     * Report label cache
     * @var string 
     */
    protected $_reportLabel;
    
    /**
     * Total Display Type
     * @var string {'price', 'points', 'qty')
     */
    protected $_totalDisplayType = 'price';
    
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
     * Additional links that will be displayed on the graph
     * @var bool
     */
    protected $_additionalLinksToDisplay = array();
    
    /**
     * Main Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('tbtreports/dashboard/metricsarea/graph.phtml');
    }
    
    /**
     * Prepare chart data
     *
     * @return void
     */
    protected function _prepareData()
    {
        $availablePeriods = array_keys($this->helper('tbtreports/adminhtml_metrics_data')->getDatePeriods());
        $period = $this->getRequest()->getParam('period');

        $this->getDataHelper()->setParam('period',
            ($period && in_array($period, $availablePeriods)) ? $period : '30d'
        );
        
        if (
            method_exists($this->getDataHelper(), 'getNoticeMessage')
            && $noticeMessage = $this->getDataHelper()->getNoticeMessage() 
        ) {
            $this->setNoticeMessage($noticeMessage);
        }
    }
    
    /**
     * Get chart url
     *
     * @param bool $directUrl
     * @return string
     */
    public function getChartUrl($directUrl = true)
    {
        $params = array(
            'cht'  => 'lc',
            'chf'  => 'bg,s,f4f4f4|c,lg,90,ffffff,0.1,ededed,0',
            'chm'  => 'B,f4d4b2,0,0,0',
            'chco' => 'db4814'
        );

        $this->_allSeries = $this->getRowsData($this->_dataRows);

        foreach ($this->_axisMaps as $axis => $attr){
            $this->setAxisLabels($axis, $this->getRowsData($attr, true));
        }

        $timezoneLocal = Mage::app()->getStore()->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);

        list ($dateStartZ, $dateEndZ) = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($this->getDataHelper()->getParam('period'), '', '', true);

        $dateStart = new DateTime($dateStartZ->toString('Y-MM-dd HH:mm:ss'));
        $dateEnd = new DateTime($dateEndZ->toString('Y-MM-dd HH:mm:ss'));

        $dates = array();
        $datas = array();

        $this->_cummulativeValue = $this->getDataHelper()->getTotalBeforePeriod();        
        while($dateStart <= $dateEnd){
            switch ($this->getDataHelper()->getParam('period')) {
                case '30d':
                    $d = $dateStart->format('Y-m-d');
                    $dateStart->modify('+1 day');
                    break;
                case '90d':
                    $d = $dateStart->format('Y-m-d');
                    $dateStart->modify('+1 day');
                    break;
                case '365d':
                    $d = $dateStart->format('Y-m-d');
                    $dateStart->modify('+1 day');
                    break;
            }

            if (count($this->getAllSeries()) > 0) {
                foreach ($this->getAllSeries() as $index=>$serie) {
                    if (in_array($d, $this->_axisLabels['x'])) {
                        $amountVal = (float)array_shift($this->_allSeries[$index]);
                        $this->_cummulativeValue += $amountVal;
                        $datas[$index][] = $this->_cummulativeValue;
                    } else {
                        $datas[$index][] = $this->_cummulativeValue;
                    }
                }
            } else {
                $datas[0][] = $this->_cummulativeValue;
            }

            $dates[] = $d;
        }

        $this->_rangeDateFrom = $dates[0];
        $this->_rangeDateTo = $dateEnd->format('Y-m-d');

        /**
         * Skipping Some Data
         */
        $this->_skipPartData($dates, $datas, $this->_axisMaps['y']);

        /**
         * setting skip step
         */
        if (count($dates) < 8) {
            $c = 0;
        } else {
            $c = abs(round(count($dates) / 8) - 1);
        }
        
        /**
         * skipping some x labels for good reading
         */
        $i=0;
        foreach ($dates as $k => $d) {
            if ($i == $c) {
                $dates[$k] = $d;
                $i = 0;
            } else {
                $dates[$k] = '';
                $i++;
            }
        }

        $this->_axisLabels['x'] = $dates;
        $this->_allSeries = $datas;

        /* Google encoding values */
        if ($this->_encoding == "s") {
            // simple encoding
            $params['chd'] = "s:";
            $dataDelimiter = "";
            $dataSetdelimiter = ",";
            $dataMissing = "_";
        } else {
            // extended encoding
            $params['chd'] = "e:";
            $dataDelimiter = "";
            $dataSetdelimiter = ",";
            $dataMissing = "__";
        }

        if (count($this->getAllSeries()) == 0) {
            $localmaxvalue = array(0);
            $localminvalue = array(0);
            $localmaxlength = array(0);
        }
        
        /* process each string in the array, and find the max length */
        foreach ($this->getAllSeries() as $index => $serie) {
            $localmaxlength[$index] = sizeof($serie);
            $localmaxvalue[$index] = max($serie);
            $localminvalue[$index] = min($serie);
        }

        if (is_numeric($this->_max)) {
            $maxvalue = $this->_max;
        } else {
            $maxvalue = max($localmaxvalue);
        }
        if (is_numeric($this->_min)) {
            $minvalue = $this->_min;
        } else {
            $minvalue = min($localminvalue);
        }

        /* default values */
        $yrange = 0;
        $yLabels = array();
        $miny = 0;
        $maxy = 0;
        $yorigin = 0;

        $maxlength = max($localmaxlength);
        if ($minvalue >= 0 && $maxvalue >= 0) {
            $miny = 0;
            if ($maxvalue > 10) {
                $p = pow(10, $this->_getPow($maxvalue));
                $maxy = (ceil($maxvalue/$p))*$p;
                $yLabels = range($miny, $maxy, $p);
            } else {
                $maxy = ceil($maxvalue+1);
                $yLabels = range($miny, $maxy, 1);
            }
            $yrange = $maxy;
            $yorigin = 0;
        }

        $chartdata = array();

        foreach ($this->getAllSeries() as $index => $serie) {
            $thisdataarray = $serie;
            if ($this->_encoding == "s") {
                /* SIMPLE ENCODING */
                for ($j = 0; $j < sizeof($thisdataarray); $j++) {
                    $currentvalue = $thisdataarray[$j];
                    if (is_numeric($currentvalue)) {
                        $ylocation = round((strlen($this->_simpleEncoding)-1) * ($yorigin + $currentvalue) / $yrange);
                        array_push($chartdata, substr($this->_simpleEncoding, $ylocation, 1) . $dataDelimiter);
                    } else {
                        array_push($chartdata, $dataMissing . $dataDelimiter);
                    }
                }
                /* END SIMPLE ENCODING */
            } else {
                /* EXTENDED ENCODING */
                for ($j = 0; $j < sizeof($thisdataarray); $j++) {
                    $currentvalue = $thisdataarray[$j];
                    if (is_numeric($currentvalue)) {
                        if ($yrange) {
                         $ylocation = (4095 * ($yorigin + $currentvalue) / $yrange);
                        } else {
                          $ylocation = 0;
                        }
                        $firstchar = floor($ylocation / 64);
                        $secondchar = $ylocation % 64;
                        $mappedchar = substr($this->_extendedEncoding, $firstchar, 1)
                            . substr($this->_extendedEncoding, $secondchar, 1);
                        array_push($chartdata, $mappedchar . $dataDelimiter);
                    } else {
                        array_push($chartdata, $dataMissing . $dataDelimiter);
                    }
                }
                /* END EXTENDED ENCODING */
            }
            array_push($chartdata, $dataSetdelimiter);
        }
        $buffer = implode('', $chartdata);

        $buffer = rtrim($buffer, $dataSetdelimiter);
        $buffer = rtrim($buffer, $dataDelimiter);
        $buffer = str_replace(($dataDelimiter . $dataSetdelimiter), $dataSetdelimiter, $buffer);

        $params['chd'] .= $buffer;

        $labelBuffer = "";
        $valueBuffer = array();
        $rangeBuffer = "";

        if (sizeof($this->_axisLabels) > 0) {
            $params['chxt'] = implode(',', array_keys($this->_axisLabels));
            $indexid = 0;
            foreach ($this->_axisLabels as $idx=>$labels){
                if ($idx == 'x') {
                    /* Format date */
                    foreach ($this->_axisLabels[$idx] as $_index=>$_label) {
                        if ($_label != '') {
                            switch ($this->getDataHelper()->getParam('period')) {
                                case '30d':
                                    $this->_axisLabels[$idx][$_index] = $this->formatDate(
                                        new Zend_Date($_label, 'Y-MM-d')
                                    );
                                    break;
                                case '90d':
                                    $this->_axisLabels[$idx][$_index] = $this->formatDate(
                                        new Zend_Date($_label, 'Y-MM-d')
                                    );
                                    break;
                                case '365d':
                                    $this->_axisLabels[$idx][$_index] = $this->formatDate(
                                        new Zend_Date($_label, 'Y-MM-d')
                                    );
                                    break;
                            }
                        } else {
                            $this->_axisLabels[$idx][$_index] = '';
                        }

                    }

                    $tmpstring = implode('|', $this->_axisLabels[$idx]);

                    $valueBuffer[] = $indexid . ":|" . $tmpstring;
                    if (sizeof($this->_axisLabels[$idx]) > 1) {
                        $deltaX = 100/(sizeof($this->_axisLabels[$idx])-1);
                    } else {
                        $deltaX = 100;
                    }
                } else if ($idx == 'y') {
                    $valueBuffer[] = $indexid . ":|" . implode('|', $yLabels);
                    if (sizeof($yLabels)-1) {
                        $deltaY = 100/(sizeof($yLabels)-1);
                    } else {
                        $deltaY = 100;
                    }
                    /* setting range values for y axis */
                    $rangeBuffer = $indexid . "," . $miny . "," . $maxy . "|";
                }
                $indexid++;
            }
            $params['chxl'] = implode('|', $valueBuffer);
        };

        /* chart size */
        $params['chs'] = $this->getWidth().'x'.$this->getHeight();

        if (isset($deltaX) && isset($deltaY)) {
            $params['chg'] = $deltaX . ',' . $deltaY . ',1,0';
        }

        /* return the encoded data */
        if ($directUrl) {
            $p = array();
            foreach ($params as $name => $value) {
                $p[] = $name . '=' .urlencode($value);
            }
            return self::API_URL . '?' . implode('&', $p);
        } else {
            $gaData = urlencode(base64_encode(json_encode($params)));
            $gaHash = Mage::helper('adminhtml/dashboard_data')->getChartDataHash($gaData);
            $params = array('ga' => $gaData, 'h' => $gaHash);
            return $this->getUrl('*/dashboard/tunnel', array('_query' => $params));
        }
    }
    
    /**
     * Getter for Total Value computed based on type
     * @return string|int
     */
    public function getTotalValue()
    {
        if ($this->_totalDisplayType === 'price') {
            return Mage::helper('core')->formatPrice($this->_cummulativeValue);
        }
        
        if ($this->_totalDisplayType === 'qty') {
            return Mage::helper('rewards')->formatNumberByLocale($this->_cummulativeValue);
        }
        
        if ($this->_totalDisplayType === 'points') {
            return Mage::helper('rewards')
                ->getPointsString(array(1 => $this->_cummulativeValue));
        }
        
        return Mage::helper('rewards')->formatNumberByLocale($this->_cummulativeValue);
    }
    
    /**
     * Getter for Total label
     * @return string
     */
    public function getTotalLabel()
    {
        return Mage::helper('tbtreports')->__('All Time:');
    }
    
    /**
     * Getter for Subtotal value based on type
     * @return string|int
     */
    public function getSubtotalValue()
    {
        if ($this->_totalDisplayType === 'price') {
            return Mage::helper('core')->formatPrice($this->getDataHelper()->getTotalBeforePeriod());
        }
        
        if ($this->_totalDisplayType === 'qty') {
            return Mage::helper('rewards')->formatNumberByLocale(
                $this->getDataHelper()->getTotalBeforePeriod()
            );
        }
        
        if ($this->_totalDisplayType === 'points') {
            return Mage::helper('rewards')
                ->getPointsString(array(1 => $this->getDataHelper()->getTotalBeforePeriod()), false, true);
        }
        
        return Mage::helper('rewards')->formatNumberByLocale(
            $this->getDataHelper()->getTotalBeforePeriod()
        );
    }
    
    /**
     * Get the difference between the subtotal and total value based on type
     * @return type
     */
    public function getTotalDifference()
    {
        $totalDifference = $this->_cummulativeValue - $this->getDataHelper()->getTotalBeforePeriod();
        if ($this->_totalDisplayType === 'price') {
            return Mage::helper('core')->formatPrice($totalDifference);
        }
        
        if ($this->_totalDisplayType === 'qty') {
            return Mage::helper('rewards')->formatNumberByLocale($totalDifference);
        }
        
        if ($this->_totalDisplayType === 'points') {
            return Mage::helper('rewards')->getPointsString(array(1 => $totalDifference), false, true);
        }
        
        return Mage::helper('rewards')->formatNumberByLocale($totalDifference);
    }
    
    /**
     * Getter for total difference label
     * @return string
     */
    public function getDifferenceLabel()
    {
        $dateFrom = date('F j, Y', strtotime($this->_rangeDateFrom));
        $dateTo = date('F j, Y', strtotime($this->_rangeDateTo));

        return Mage::helper('tbtreports')->__('Between %s - %s', $dateFrom, $dateTo);
    }
    
    /**
     * Getter for Subtotal Label
     * @return string
     */
    public function getSubtotalLabel()
    {
        return Mage::helper('tbtreports')->__('Total before %s', date('F j, Y', strtotime($this->_rangeDateFrom)));
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
     * Settor for Total Display Type used to compute value
     * @param string $totalDisplayType
     * @return \TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Graph
     */
    public function setTotalDisplayType($totalDisplayType)
    {
        $this->_totalDisplayType = $totalDisplayType;
        
        return $this;
    }
    
    /**
     * Getter for Total Display Type used to compute value
     * @return string
     */
    public function getTotalDisplayType()
    {
        return $this->_totalDisplayType;
    }

    /**
     * Reduce the data so chart limits are not reached
     * @param array $dates
     * @param array $datas
     * @param string|int $datasKey
     * @return \TBT_Reports_Block_Adminhtml_Dashboard_MetricsArea_Graph
     */
    protected function _skipPartData(&$dates, &$datas, $datasKey)
    {
        $numberOfEntries = count($dates);
        $keepIndexMultiplier = 3;

        if (!isset($datas[$datasKey])) {
            $datasKey = 0;
        }

        if ($numberOfEntries < 40) {
            return $this;
        }

        /**
         * Make sure to keep first and last index
         */
        for ($i=1; $i < ($numberOfEntries-1); $i++) {
            if ($i % $keepIndexMultiplier === 0) {
                unset($dates[$i]);
                unset($datas[$datasKey][$i]);
            }
        }

        /**
         * Resetting the key arrays
         */
        $dates = array_values($dates);
        $datas[$datasKey] = array_values($datas[$datasKey]);

        return $this;
    }
    
    /**
     * Retrieve all links to display
     * @return array
     */
    public function getAllAdditionalLinks()
    {
        return $this->_additionalLinksToDisplay;
    }
    
    /**
     * Should we display a link that redirects to orders made by loyalty members
     * @return string
     */
    public function getAdditionalLink($key)
    {
        if (!empty($this->_additionalLinksToDisplay[$key])) {
            return $this->_additionalLinksToDisplay[$key];
        }
        
        return '';
    }
    
    /**
     * Add an additional link
     * @param string $key
     * @param string $link
     * @param string $text
     */
    public function addAdditionalLink($key, $link, $text)
    {
        $this->_additionalLinksToDisplay[$key] = array(
            'link' => $link,
            'text' => $text
        );
    }
}

