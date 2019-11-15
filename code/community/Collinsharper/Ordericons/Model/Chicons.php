<?php

class Collinsharper_Ordericons_Model_Chicons extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
       $this->_init("chordericons/chicons");
    }




    protected function _beforeSave()
    {
        parent::_beforeSave();
        mage::log(__METHOD__ . __LINE__ );

        $time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        $this->setUpdatedAt($time);
        if(!$this->getId() || !$this->getCreatedAt()) {
            $this->setCreatedAt($time);
        }
        return $this;
    }



}
	 
