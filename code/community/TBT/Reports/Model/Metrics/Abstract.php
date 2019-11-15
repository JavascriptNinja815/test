<?php

/**
 * Class TBT_Reports_Model_Metrics_Abstract
 *
 * @method string getMetricInfo()
 */
abstract class TBT_Reports_Model_Metrics_Abstract extends Mage_Core_Model_Abstract
{
    const METRIC_TYPE_CURRENCY = 'currency';
    const METRIC_TYPE_NUMBER   = 'number';

    protected $_extraDebug = false;
    protected $_debug = array();
    protected $_value = null;
    protected $_cachedStartDate = null;
    protected $_cachedEndDate = null;

    public abstract function getMetricName();
    protected abstract function _calculate($startDate = null, $endDate = null);


    /**
     * Will return the type of this metric.
     * Default is 'number'
     * @return string
     */
    public function getMetricType()
    {
        return self::METRIC_TYPE_NUMBER;
    }

    /**
     * Public access to a cached copy for value of _calculate() function
     *
     * @see TBT_Reports_Model_Metrics_Abstract::_calculate()
     * @return mixed
     */
    public function getMetricValue($startDate = null, $endDate = null)
    {
        if (is_null($this->_value) ||
            $startDate != $this->_cachedStartDate ||
            $endDate != $this->_cachedEndDate
        ) {

            $this->_cachedStartDate = $startDate;
            $this->_cachedEndDate = $endDate;
            $this->_value = $this->_calculate($startDate, $endDate);
        }

        return $this->_value;
    }

    /**
     * Will return the formatted value of this metric
     *
     * @param string $startDate (optional) in UTC time
     * @param string $endDate (optional) in UTC time
     * @return mixed|string
     * @throws Zend_Locale_Exception
     */
    public function getFormattedValue($startDate = null, $endDate = null)
    {
        $value = null;
        try {
            $value = $this->getMetricValue($startDate, $endDate);

        } catch (Exception $e) {
            Mage::helper('rewards/debug')->logException($e);
        }

        if (is_null($value)) {
            return "-";
        }

        switch ($this->getMetricType()) {

            case self::METRIC_TYPE_NUMBER:
                $formatted = Zend_Locale_Format::toNumber($value, array(
                    'locale' => new Zend_Locale(Mage::app()->getLocale()->getLocale()))
                );
                break;

            case self::METRIC_TYPE_CURRENCY:
                $formatted = Mage::helper('core')->formatCurrency($value, false);
                break;

            default:
                $formatted = $value;

        }

        return $formatted;
    }

    /**
     * @return TBT_Reports_Model_Mysql4_Customer_Collection
     */
    protected function _getCustomerCollection()
    {
        return Mage::getResourceModel('tbtreports/customer_collection');
    }

    /**
     * @return TBT_Reports_Model_Mysql4_Order_Collection
     */
    protected function _getOrderCollection()
    {
        return Mage::getResourceModel('tbtreports/order_collection');
    }

    /**
     * Allow extra debugging when this flag is on
     * @see $this::getDebugInfo()
     * @return $this
     */
    public function allowExtraDebugging()
    {
        $this->_extraDebug = true;
        return $this;
    }

    /**
     * @return array containing some debug information
     */
    public function getDebugInfo()
    {
        return $this->_debug;
    }

    /**
     * Alias for translate method in the helper
     * @return mixed
     */
    public function __()
    {
        $args = func_get_args();
        $helper = Mage::helper('tbtreports');
        return call_user_func_array(array($helper, '__'), $args);
    }

    /**
     * Will determine if this metric is ready and available
     * By default it depends on the order_indexer
     * @return mixed
     */
    public function isReady()
    {
        return Mage::helper('tbtreports/indexer_order')->isReady();
    }

    public function logDebug($message)
    {
        if ($this->_extraDebug) {
            // @todo change this to Mage::log($message, null, 'tbtreports.log', true);
            array_push($this->_debug,  $message);
        }
    }
}
