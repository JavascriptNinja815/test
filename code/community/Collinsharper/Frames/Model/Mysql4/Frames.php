<?php
/**
 * Collinsharper/Frames/Model/Mysql4/Frames.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chshopby
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper_Frames_Model_Mysql4_Frames
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chshopby
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Frames_Model_Mysql4_Frames extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('chframes/frames', 'entity_id');
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {

        $time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));

        if(!$object->getData('forced_updated_at')) {
            $object->setUpdatedAt($time);
        }

        if(!$object->getData('created_at')) {
            $object->setData('created_at', $time);
        }


        return parent::_beforeSave($object);
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {

        if(!$object->getData('forced_updated_at') && $object->getData('reel_id')) {

            $time = $object->getData('updated_at');
            if(!$time) {
                $time = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
            }

            //TODO how do we best handle this?
            $tableName = Mage::getSingleton("core/resource")->getTablename('ch_reels');
            $sql = "update {$tableName} set updated_at = ?, viewed_at = ?, final_reel_file = '' where entity_id = ? limit 1";
            $write = Mage::getSingleton("core/resource")->getConnection("core_write");
            $data = $write->query($sql, array($time, $time, $object->getData('reel_id')));

        }

        return parent::_afterSave($object);
    }


}
