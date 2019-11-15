<?php

class Collinsharper_Beanstreamprofiles_Helper_Messages extends Mage_Core_Helper_Abstract
{

    var $messages = false;
    var $types = array();
    const RSPADDR = 'rspCodeAddr';
    const RSPSS = 'rspCodeSafeScan';
    const RSPSSID = 'rspCodeSafeScanId';
    const FILENAME = 'Collinsharper_Beantreamprofiles_Cav_Messages.txt';
    function __construct()
    {
        $this->types = array(self::RSPADDR, self::RSPSS, self::RSPSSID );
        $file =  Mage::getBaseDir('app') . DS . 'locale' . DS . 'en_US' . DS . self::FILENAME;
        if(!file_exists($file)) {
            Mage::throwException($this->__('Cannot find file for cav messages (%s)'), $file);
        }
        $this->messages = array();
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 2000, "\t",'"')) !== FALSE)
            {
                $num = count($data);
                // we only touch rows with 3
                if($num != 3) {
                    continue;
                }

                if(strpos($data[0],"#") === 0) {
                    continue;
                }

                if(!in_array($data[0],$this->types)) {
                    mage::log(__FILE__ . __LINE__ . " skipping MESSAGE" . print_r($data,1));
                    continue;
                }

                foreach($data as $k => $v) {
                    $data[$k] = trim($v);
                }

                if(!isset($this->messages[$data[0]]))
                {
                    $this->messages[$data[0]] = array();
                }

                $this->messages[$data[0]][$data[1]] = $data[2];
            }
           // mage::log(__FILE__ . __LINE__ . " skipping MESSAGE" . print_r($this->messages,1));
            fclose($handle);
        }
    }

    function getrspCodeAddr($id)
    {
        if(isset($this->messages[self::RSPADDR][$id]))
        {
            return $this->messages[self::RSPADDR][$id];
        }
        mage::log(__FILE__ . __LINE__ . " FAILED TO FIND CAV MESSAGE" . $id);
        return "";
    }

    function gerspCodeSafeScan($id)
    {
        if(isset($this->messages[self::RSPSS][$id]))
        {
            return $this->messages[self::RSPSS][$id];
        }
        mage::log(__FILE__ . __LINE__ . " FAILED TO FIND CAV MESSAGE" . $id);
        return "";
    }

    function getrspCodeSafeScanId($id)
    {
        if(isset($this->messages[self::RSPSSID][$id]))
        {
            return $this->messages[self::RSPSSID][$id];
        }
        mage::log(__FILE__ . __LINE__ . " FAILED TO FIND CAV MESSAGE" . $id);
        return "";
    }
}