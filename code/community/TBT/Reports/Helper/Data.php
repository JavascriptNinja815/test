<?php

class TBT_Reports_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Will return the launch date of the loyalty program in UTC time
     * @todo needs implementation!
     * @return string
     */
    public function getLoyaltyLaunchDate()
    {
        return "2012-01-01 5:00:00";
    }

    /**
     * Will determine if we should pull dev data or not
     * @todo make this a config setting
     * @return bool
     */
    public function shouldReportOnDevMode()
    {
        return true;
    }

    /**
     * Will determine if we should pull live data or not
     * @todo make this a config setting
     * @return bool
     */
    public function shouldReportOnLiveMode()
    {
        return true;
    }

    /**
     * Will record indexer performance
     * @todo make this a config setting
     * @return bool
     */
    public function shouldRecordIndexerPerformance()
    {
        return true;
    }
}
