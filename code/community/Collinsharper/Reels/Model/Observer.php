<?php


class Collinsharper_Reels_Model_Reels
{
    public function onBeforeSaveObjects($event)
    {
        mage::log(__METHOD__);

        $object = $event->getEvent()->getObject();
        $time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));

        $object->setUpdatedAt($time);

        if(!$object->getEntityId()) {
            $object->setCreatedAt($time);
        }
        return $this;
    }

}

