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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper Data
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Helper_Datetime extends Mage_Core_Helper_Abstract {

    const FORMAT_MYSQL_DATE_PHP       = 'Y-m-d';
    const FORMAT_MYSQL_DATETIME_PHP   = 'Y-m-d H:i:s';
    const FORMAT_MYSQL_DATE_ZEND      = 'YYYY-MM-dd';
    const FORMAT_MYSQL_DATETIME_ZEND  = 'YYYY-MM-dd HH:mm:ss';

    /**
     * Fetches the current date in the format 'Y-m-d'
     * and based on the currently loaded store.
     * @see TBT_Rewards_Helper_Datetime::xDaysAgo()
     * @nelkaake Moved on Wednesday September 22, 2010: moved from Data helper
     *
     * @return string
     */
    public function now($dayOnly = TRUE, $utc = FALSE) {
        return $this->xDaysAgo(0, $dayOnly, $utc);
    }

    /**
     * Fetches tomorrow's date in the format 'Y-m-d'
     * and based on the currently loaded store.
     * @see TBT_Rewards_Helper_Datetime::xDaysAgo()
     * @nelkaake Moved on Wednesday September 22, 2010: moved from Data helper
     *
     * @return string
     */
    public function tomorrow($dayOnly = TRUE, $utc = FALSE)
    {
        return $this->xDaysAgo(-1, $dayOnly, $utc);
    }

    /**
     * Fetches yesterday's date in the format 'Y-m-d'
     * and based on the currently loaded store.
     * @see TBT_Rewards_Helper_Datetime::xDaysAgo()
     * @nelkaake Moved on Wednesday September 22, 2010: moved from Data helper
     *
     * @return string
     */
    public function yesterday($dayOnly = TRUE, $utc = FALSE)
    {
        return $this->xDaysAgo(1, $dayOnly, $utc);
    }

    /**
     * Fetches a date in the format 'Y-m-d' or 'Y-m-d H:i:s' based on the currently loaded store.
     *
     * @param number $xDays. Number of days in the past to query the date for. Ex. -1 for tomorrow, 0 for today, 1 for yesterday
     * @param boolean $dayOnly. Will return format of 'Y-m-d' if true (default), 'Y-m-d H:i:s' if false.
     * @param boolean $utc. If true, will return final result in UTC timezone. If false (default), will return result in store's locale timezone
     * @return string formatted date
     */
    public function xDaysAgo($xDays = 0, $dayOnly = TRUE, $utc = FALSE)
    {
        $totalSecondsInDay = 60 * 60 * 24;
        $xSecondsAgo = $xDays * $totalSecondsInDay;

        $format = ($dayOnly ? self::FORMAT_MYSQL_DATE_PHP : self::FORMAT_MYSQL_DATETIME_PHP);
        $zend_format = ($dayOnly ? self::FORMAT_MYSQL_DATE_ZEND : self::FORMAT_MYSQL_DATETIME_ZEND);
        $now = strtotime(gmdate(self::FORMAT_MYSQL_DATETIME_PHP));
        $xDaysAgo = gmdate($format,  $now - $xSecondsAgo);

        if ($utc) {
            $str_xDaysAgo = $xDaysAgo;
        } else {
            $zend_xDaysAgo = Mage::app()->getLocale()->date($xDaysAgo, $zend_format);
            $str_xDaysAgo = $zend_xDaysAgo->toString($zend_format);
        }

        return $str_xDaysAgo;
    }

    /**
     * Will populate a Zend_Date object with the date specified
     * Does not convert to a different timezone!
     *
     * @param string|integer|Zend_Date|array  $date
     * @param bool $useStoreTimezone,   if false (default), will interpret input as UTC date,
     *                                  if true, input will be interpreted as store's timezone.
     *
     * @param string $format format of the input (default: TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND)
     * @return Zend_Date object with correct timezone based on $useStoreTimezone
     * @throws Zend_Date_Exception
     */
    public function getZendDate($date = null, $useStoreTimezone = false, $format = self::FORMAT_MYSQL_DATETIME_ZEND)
    {
        if ($useStoreTimezone) {
            $zendDate = Mage::app()->getLocale()->date();

        } else {
            $locale = Mage::app()->getLocale()->getLocale();
            $zendDate = new Zend_Date(null, null, $locale);
            $zendDate->setTimezone('UTC');
        }

        if ($date) {
            $zendDate->set($date, $format);
        }

        return $zendDate;
    }

    /**
     * Will accept a date string and use strtotime() in explicit UTC timezone to convert to timestamp
     * then will use date function to format timestamp as requested.
     * @param $dateString, any string accepted by strtotime() (assumes UTC if no timezone specified)
     * @param string $toFormat, output format accepted by PHP's date() function
     * @return string
     */
    public function reformatDateString($dateString, $toFormat = self::FORMAT_MYSQL_DATETIME_PHP)
    {
        $currentTimezone = @date_default_timezone_get();
        @date_default_timezone_set("UTC");
        $utcTime = strtotime($dateString);
        @date_default_timezone_set($currentTimezone);
        return gmdate($toFormat, $utcTime);
    }

    /**
     * Get the timezone of the specified store
     *
     * @param null|string|bool|int|Mage_Core_Model_Store $id
     * @return null|string
     */
    public function getStoreTimezone($store = null)
    {
        return Mage::app()->getStore($store)->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
    }

    /**
     * Will add the offset to specified date
     *
     * @param string|Zend_Date $date (if string, in UTC timezone)
     * @param int $offset number of seconds to add or subtract
     * @return string|Zend_Date (depending on what type was passed in)
     */
    public function addOffsetToDate($date, $offset)
    {
        $offsetAbs = abs($offset);
        if (empty($offset) || empty($date)) return $date;
        
        $returnAsString = false;
        if (is_string($date)) {
            $returnAsString = true;
            $date = $this->getZendDate($date);
        }

        if ($date instanceof Zend_Date) {
            if ($offset > 0) $date->add($offsetAbs, Zend_Date::SECOND);
            if ($offset < 0) $date->sub($offsetAbs, Zend_Date::SECOND);
        } else {
            throw new Exception('Please pass in string or Zend_Date object');
        }

        if ($returnAsString) {
            return $date->toString(self::FORMAT_MYSQL_DATETIME_ZEND);
        }

        return $date;
    }

    // @nelkaake Moved on Wednesday September 22, 2010: moved from Data helper
    public function getCurrentFromDate() {
        $fromDate = mktime ( 0, 0, 0, date ( 'm' ), date ( 'd' ) - 1 );
        if (is_string ( $fromDate )) {
            $fromDate = strtotime ( $fromDate );
        }
        return $fromDate;
    }

    // @nelkaake Moved on Wednesday September 22, 2010: moved from Data helper
    public function getCurrentToDate() {
        $toDate = mktime ( 0, 0, 0, date ( 'm' ), date ( 'd' ) + 1 );
        if (is_string ( $toDate )) {
            $toDate = strtotime ( $toDate );
        }
        return $toDate;
    }

    /**
     * Covert seconds into Days Hours Minutes Seconds
     * @param int $seconds seconds
     * @param boolean $returnString
     * @return Format string or array
     */
    public function secondsToDayFormat($seconds)
    {
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;
        $days = $hours = $minutes = 0;
        $remainSec = $seconds;

        // Check input seconds has days
        if ($seconds >= $secondsInADay) {
            $days = floor($seconds / $secondsInADay);
            $remainSec = $seconds - ($days * $secondsInADay);
        }

        // Check remaining seconds has hours
        if ($remainSec >= $secondsInAnHour) {
            $hours = floor($remainSec / $secondsInAnHour);
            $remainSec = $remainSec - ($hours * $secondsInAnHour);
        }

        // Check remaining seconds has minute
        if ($remainSec >= $secondsInAMinute) {
            $minutes = floor($remainSec / $secondsInAMinute);
            $remainSec = $remainSec - ($minutes * $secondsInAMinute);
        }

        return array(
            'd' => (int) $days,
            'h' => (int) $hours,
            'm' => (int) $minutes,
            's' => (int) $remainSec,
        );
    }
}
