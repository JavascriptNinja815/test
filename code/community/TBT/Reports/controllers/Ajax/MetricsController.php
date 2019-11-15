<?php

require_once(Mage::getModuleDir('controllers', 'TBT_Reports') . DS . 'AjaxController.php');
class TBT_Reports_Ajax_MetricsController extends TBT_Reports_AjaxController
{
    /**
     * Returns metric data in JSON format
     * accepts optional 'start_date' and 'end_date' request parameters
     * @return $this
     */
    public function loyaltyCustomersRevenueAction()
    {
        $metric = Mage::getModel('tbtreports/metrics_loyaltyCustomersRevenue');
        $metricData = $this->_getMetricData($metric);
        $this->_useExplicitDatesFromOrderIndexTable($metricData);

        return $this->jsonResponse($metricData);
    }

    /**
     * Returns metric data in JSON format
     * accepts optional 'start_date' and 'end_date' request parameters
     * @return $this
     */
    public function revenueFromReferredCustomersAction()
    {
        $metric = Mage::getModel('tbtreports/metrics_referredCustomersRevenue');
        $metricData = $this->_getMetricData($metric);
        $this->_useExplicitDatesFromOrderIndexTable($metricData);

        return $this->jsonResponse($metricData);
    }

    /**
     * Returns metric data for all metrics in JSON format
     * accepts optional 'start_date' and 'end_date' request parameters
     * @return $this
     */
    public function allMetricsAction()
    {
        $allMetricsData = array();
        $metrics = array(
            Mage::getModel('tbtreports/metrics_loyaltyCustomersRevenue'),
            Mage::getModel('tbtreports/metrics_referredCustomersRevenue')
        );

        foreach ($metrics as $metric) {
            $data = $this->_getMetricData($metric);
            $this->_useExplicitDatesFromOrderIndexTable($data);
            array_push($allMetricsData, $data);
        }

        return $this->jsonResponse($allMetricsData);
    }

    /**
     * Will extract data about the provided metric
     *
     * @param TBT_Reports_Model_Metrics_Abstract $metric
     * @return array
     */
    protected function _getMetricData($metric)
    {
        /**
         * Dates are optional strings (MySQL formatted) in UTC timezone.
         * If none specified, there will be no start_date or end_date limits
         */
        $startDate = $this->getRequest()->getParam('start_date', null);
        $endDate = $this->getRequest()->getParam('end_date', null);
        $metricValue = $error = null;

        try {
            // Implicit input checking using ZendDate
            $zendStartDate = !is_null($startDate) ? $this->_getDateTimeHelper()->getZendDate($startDate) : null;
            $zendEndDate = !is_null($startDate) ? $this->_getDateTimeHelper()->getZendDate($startDate) : null;
            $metricValue = $metric->getMetricValue($startDate, $endDate);

        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        $zendFormat = TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND;
        $metricData = array (
            'name'          => $metric->getMetricName(),
            'type'          => $metric->getMetricType(),
            'info'          => $metric->getMetricInfo(),
            'start_date'    => $zendStartDate? $zendStartDate->toString($zendFormat) : null,
            'end_date'      => $zendEndDate? $zendEndDate->toString($zendFormat) : null,
            'value'         => $metricValue,
            'error'         => $error
        );

        return $metricData;
    }

    /**
     * Will modify input array and use explicit dates using the order index table
     * in place of original metric start and end dates if either are null
     *
     * @param array &$metricDataArray
     * @return array mixed
     */
    protected function _useExplicitDatesFromOrderIndexTable(&$metricDataArray)
    {
        if (is_null($metricDataArray['start_date'])) {

            $explicitStartDate = Mage::helper('tbtreports/indexer_order')->getEarliestRecordDate();
            $metricDataArray['start_date'] = $explicitStartDate;
        }

        if (is_null($metricDataArray['end_date'])) {
            $explicitEndDate = $this->_getDateTimeHelper()->now(false, true);
            $metricDataArray['end_date'] = $explicitEndDate;
        }

        return $metricDataArray;
    }

    /**
     * @return TBT_Rewards_Helper_Datetime
     */
    protected function _getDateTimeHelper()
    {
        return Mage::helper('rewards/datetime');
    }
}