<?php
class TBT_Reports_Model_Metrics_Social_GooglePlusRewards extends TBT_Reports_Model_Metrics_Abstract
{
    public function getMetricName()
    {
        return $this->__("Rewards for Google+");
    }

    public function getMetricInfo()
    {
        return $this->__("Number of times customers have been awarded for engaging with Google+.");
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