<?php

class Collinsharper_Beanstreamprofiles_Model_Session extends Mage_Core_Model_Session_Abstract
{
 
	protected $_storedaccount;


    public function getStoredAccount()
    {
        return $this->_storedaccount;
    }

    public function setStoredAccount($x)
    {
        return $this->_storedaccount = $x;
    }

    public function __construct()
    {
        $namespace = 'beanstreamprofiles';
        $this->init($namespace);
		//	Mage::dispatchEvent('customer_session_init', array('customer_session'=>$this));
    }
}