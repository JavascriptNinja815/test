<?php


class TBT_Reports_Model_Metrics_LoyaltyCustomersRevenue extends TBT_Reports_Model_Metrics_Abstract
{
    protected $_sum = 0;

    public function getMetricName()
    {
        return $this->__("Revenue from Loyalty Customers");
    }

    public function getMetricInfo()
    {
        $html = $this->__("Consists of revenue produced by any customer who:") .
                "
                <br/>
                <br/>
                <ul>
                    <li>" . $this->__("has ever spent points.") . "</li>
                    <li>" . $this->__("has earned points from at least 2 orders.") . "</li>
                    <li>" . $this->__("has earned points from at least one order and one other action (ie: reviews, social media shares).") . "</li>
                    <li>" . $this->__("has ever transitioned to a new customer tier as a result of reaching a milestone.") . "</li>
                    <li>" . $this->__("has been referred by someone else.") . "</li>
                </ul>
                <br/>
                " . $this->__("Such customers are considered to be active members of your loyalty program.");

        return $html;
    }

    public function getMetricType()
    {
        return self::METRIC_TYPE_CURRENCY;
    }

    /**
     * Will calculate the total revenue from loyalty customers
     * between startDate and endDate for completed orders
     *
     * @param string $startDate (optional) in UTC time
     * @param string $endDate (optional) in UTC time
     * @return float
     */
    protected function _calculate($startDate = null, $endDate = null)
    {
        $ordersFromLoyaltyCustomers = $this->_getOrderCollection()
            ->onlyCompleteOrders()
            ->onlyOrdersByLoyaltyCustomers()
            ->limitPeriod($startDate, $endDate);

        $totalRevenue = $ordersFromLoyaltyCustomers->getTotalRevenue();
        $this->_sum += (float) $totalRevenue;

        if ($this->_extraDebug) {
            $debug = "\n".
                "\tPeriod between {$startDate} & {$endDate}:\n".
                "\t\tOrders by loyal customers count:\t\t".$ordersFromLoyaltyCustomers->getSize()."\n".
                "\t\tOrders by loyal customers revenue:\t\t{$totalRevenue}\n".
                "\t\tTotals so far:\t\t\t\t{$this->_sum }\n";
            $this->logDebug($debug);
        }

        return $totalRevenue;
    }
}