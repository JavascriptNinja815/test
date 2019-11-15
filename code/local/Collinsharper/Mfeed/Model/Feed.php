<?php

class Collinsharper_Mfeed_Model_Feed extends Mage_AdminNotification_Model_Feed
{
	private $_code = 'chmfeed';
    const XML_USE_HTTPS_PATH    = 'chmfeed/feed/use_https';
    const XML_FEED_URL_PATH     = 'chmfeed/feed/url';
    const XML_FREQUENCY_PATH    = 'chmfeed/feed/check_frequency';
	const XML_FREQUENCY_ENABLE  = 'chmfeed/feed/enabled';
    const XML_LAST_UPDATE_PATH  = 'chmfeed/feed/last_update';

	
	public static function check(){
		if(!Mage::getStoreConfig(self::XML_FREQUENCY_ENABLE)){		
			return;
		}
		return Mage::getModel('chmfeed/feed')->checkUpdate();
	}
	
    public function getFrequency()
    {
        return Mage::getStoreConfig(self::XML_FREQUENCY_PATH) * 3600;
    }

    public function getLastUpdate()
    {		
        return Mage::app()->loadCache('chmfeed_notifications_lastcheck');
    }

    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'chmfeed_notifications_lastcheck');
        return $this;
    }
    public function getFeedUrl()
    {
		
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = (Mage::getStoreConfigFlag(self::XML_USE_HTTPS_PATH) ? 'https://' : 'http://')
                . Mage::getStoreConfig(self::XML_FEED_URL_PATH);
        }
        return $this->_feedUrl;
    }

 public function checkUpdate()
    {

        if (($this->getFrequency() + $this->getLastUpdate()) > time()) {
            return $this;
        }

        $feedData = array();

        $feedXml = $this->getFeedData();
				
        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
                $feedData[] = array(
                    'severity'      => (int)$item->comments ? (int)$item->comments : 3, // we store severity in comments meh..
                    'date_added'    => $this->getDate((string)$item->pubDate),
                    'title'         => (string)$item->title,
                    'description'   => (string)$item->description,
                    'url'           => (string)$item->link,
                );
            }
            if ($feedData) {
                Mage::getModel('adminnotification/inbox')->parse(array_reverse($feedData));
            }

			
			
        }
        $this->setLastUpdate();

        return $this;
    }

}
