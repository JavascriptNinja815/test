<?php
class TBT_Reports_Model_Metrics_Social_FacebookRewards extends TBT_Reports_Model_Metrics_Abstract
{
    public function getMetricName()
    {
        return $this->__("Rewards for Facebook");
    }

    public function getMetricInfo()
    {
        return $this->__("Number of times customers have been awarded for engaging with Facebook.");
    }

    public function getMetricType()
    {
        return self::METRIC_TYPE_NUMBER;
    }

    /**
     * @todo Requires implementation
     */
    protected function _calculate($startDate = null, $endDate = null)
    {
        return null;
    }
}