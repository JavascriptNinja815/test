<?php

class Collinsharper_Reels_Model_Mysql4_Printqueue extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {
        $this->_init('chreels/printqueue', 'entity_id');
    }


    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        // TODO load its kids?
        //   $this->loadFrames($object);

        return $this;
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {

        return $this;
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {

        if(!$object->getData('forced_updated_at')) {
            $time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            $object->setUpdatedAt($time);
        }

        if(!$object->getCreatedTime()) {
            $object->setCreatedAt($time);
        }

        return parent::_beforeSave($object);
    }


}
