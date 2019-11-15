<?php

class Collinsharper_Image3d_Helper_Marketing extends Mage_Core_Helper_Data
{

    const LIMIT_PER_DAY = 9999999;

    protected $_iosUserAgents = array('iPad', 'iPhone', 'iPod');
    public function getIosStateFilename()
    {
        return sys_get_temp_dir() . DS .  'ios-popup-counter-' . date('Y-m-d');
    }

    public function getCurrentShowsForToday()
    {

        $filename = $this->getIosStateFilename();
        if(!file_exists($filename)) {
            file_put_contents($filename, 0);
        }
        return (int)trim(file_get_contents($filename));
    }

    public function getShouldShowIosPopup()
    {
        $hasValidAgent = false;

        $shows = $this->getCurrentShowsForToday();

        $usersAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'NOAGENT!';


        foreach($this->_iosUserAgents as $agent) {
            if(stripos($usersAgent, $agent) !== false) {
                mage::log(__METHOD__ .  "We have agent ");

                $hasValidAgent = true;
                break;
            }
        }


        // we will show it to a single user once.

        $hasSeen = Mage::getSingleton('customer/session')->getHasSeenIosPopup() ? 1 : 0;


        $shouldShow = $hasValidAgent && !$hasSeen && $shows < self::LIMIT_PER_DAY;

        if($shouldShow) {
            mage::log(__METHOD__ .  "show show!");

            Mage::getSingleton('customer/session')->setHasSeenIosPopup(1);
            file_put_contents($this->getIosStateFilename(), $shows + 1);
        }

        return $shouldShow;
    }

}
