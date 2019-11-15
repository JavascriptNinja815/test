<?php

class Collinsharper_Reels_Model_Mysql4_Reels extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {
        $this->_init('chreels/reels', 'entity_id');
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

        $time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));

        if(!$object->getData('forced_updated_at')) {
            $object->setUpdatedAt($time);
        }

        if(!$object->getData('viewed_at')) {
            $object->setData('viewed_at',  $object->getUpdatedAt($time));
        } else {
            $object->setData('viewed_at', $time);
        }

        if(!$object->getData('created_at')) {
            $object->setData('created_at', $time);
        }


        return parent::_beforeSave($object);
    }


}
