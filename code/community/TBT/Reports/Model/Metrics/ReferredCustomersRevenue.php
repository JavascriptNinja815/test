<?php


class TBT_Reports_Model_Metrics_ReferredCustomersRevenue extends TBT_Reports_Model_Metrics_Abstract
{
    protected $_sum = 0;

    public function getMetricName()
    {
        return $this->__("Revenue from Referred Customers");
    }

    public function getMetricInfo()
    {
        return $this->__("Consists of revenue produced by any customer who was referred to your store through %s.", "MageRewards");
    }

    public function getMetricType()
    {
        return self::METRIC_TYPE_CURRENCY;
    }

    /**
     * Will calculate the total revenue from referred customers
     * between startDate and endDate for completed orders
     *
     * @param string $startDate (optional) in UTC time
     * @param string $endDate (optional) in UTC time
     * @return float
     */
    protected function _calculate($startDate = null, $endDate = null)
    {
        $orders = $this->_getOrderCollection()
            ->onlyCompleteOrders()
            ->onlyOrdersByReferredCustomers()
            ->limitPeriod($startDate, $endDate);

        $totalRevenue = $orders->getTotalRevenue();
        $this->_sum += (float) $totalRevenue;

        if ($this->_extraDebug) {
            $debug = "\n".
                "\tPeriod between {$startDate} & {$endDate}:\n".
                "\t\tOrders by referred customers count:\t\t".$orders->getSize()."\n".
                "\t\tOrders by referred customers revenue:\t\t{$totalRevenue}\n".
                "\t\tTotals so far:\t\t\t\t{$this->_sum }\n";
            $this->logDebug($debug);
        }

        return $totalRevenue;
    }
}