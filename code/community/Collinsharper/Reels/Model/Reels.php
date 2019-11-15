<?php
/**
 * Collinsharper/Reels/Model/Reels.php
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
 * Collinsharper_Reels_Model_Reels
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
class Collinsharper_Reels_Model_Reels extends Mage_Core_Model_Abstract
{

    private $_frames;

    const COMPLETE_STATUS = 10;
    const NEW_STATUS = 0;
    const REGEN_STATUS = 1;
    const PEND_DELETE_STATUS = 4;
    const IMPORTED_COMPLETE_STATUS = 9;
    const IMPORTED_NEW_STATUS = 11;

    public function _construct()
    {
        $this->_init('chreels/reels');
    }


    static function getStatusOptions()
    {
        return array(
            self::NEW_STATUS => mage::helper('chreels')->__('New'),
            self::REGEN_STATUS => mage::helper('chreels')->__('Regenerate'),
            self::PEND_DELETE_STATUS => mage::helper('chreels')->__('Pending Deletion'),
            self::IMPORTED_COMPLETE_STATUS  => mage::helper('chreels')->__('Imported - Complete'),
            self::IMPORTED_NEW_STATUS  => mage::helper('chreels')->__('Imported - New'),
            self::COMPLETE_STATUS => mage::helper('chreels')->__('Complete'),
        );
    }


    static function getFileStatusOptions()
    {
        return array(
            11 => mage::helper('chreels')->__('Imported Complete'),
            10 => mage::helper('chreels')->__('Complete'),
            9 => mage::helper('chreels')->__('Queue For Printing'),
        );
    }

    public function getFrames()
    {
        if (!$this->_frames) {
            $this->loadFrames();
        }
        return $this->_frames;
    }

    public function loadFrames($force = false)
    {
        if(!$this->_frames || $force) {
            $this->_frames = false;

            if($this->getEntityId()) {
                $this->_frames = Mage::getModel('chframes/frames')->getCollection();
                $this->_frames->addFieldToFilter('reel_id', $this->getEntityId());
                $this->_frames->setOrder('frame_index','asc')->load();
            }
        }
        return $this;
    }

    public function delete()
    {
        mage::log(__METHOD__ . __LINE__);
        $this->deleteFrameData();
        // TODO delete completed reel files from local and amazon?!?
        return parent::delete();
    }

    public function deleteFrameData()
    {
        if(!$this->getEntityId()) {
            throw new Exception("Uninitialized reel");
        }

        $frames = Mage::getModel('chframes/frames')->getCollection();
        $frames->addFieldToFilter('reel_id', $this->getEntityId());

        foreach ($frames as $frame) {
            $frame->delete();
        }

        // TODO purge frame files
        //$return = Mage::helper('chreels/cleaner')->cleanReelFiles($this->getId(), $this->getCustomerId());
        return $this;
    }

    public function getAjaxFrames($force = false)
    {
        $frames = array();
        
        if(!$this->_frames || $force) {
            $this->loadFrames($force);
        }

        $nonAjaxFields =
            array(
                'legacy_frame_id',
                'legacy_frame_data',
                'viewport_file',
                'left_file',
                'right_file',
                'thumb_file',
                'final_frame_status',
                'created_at',
                'updated_at',
            );


        foreach($this->_frames as $frame) {
            // TODO there is bug that saves frmaes without index.
            if($frame->getData('frame_index') == null) {
                continue;
            }

            $frame_data = json_decode($frame->getData('frame_data') ? $frame->getData('frame_data') : '{}');


            $data = $frame->getData();
            foreach($nonAjaxFields as $f) {
                unset($data[$f]);
            }

            $frames[$frame->getData('frame_index')] = $data;
            $frames[$frame->getData('frame_index')]['frame_data'] = $frame_data;
        }

        // if we dont have 8 frames we create them?
//        if(!count($frames) > 8) {
//            mage::log(__METHOD__ . " force creating fframes..");
//            for($i=0;$i<8;$i++) {
//                if(!isset($frames[$frame->getData($i)])) {
//                    $frame = Mage::getModel('chframes/frames');
//                    $frame->setData('reel_id', $this->getEntityId());
//                    $frame->setData('frame_index', $i);
//                    $frame->save();
//                    $frames[$i] = $frame->getData();
//                    $frames[$i]['frame_data'] = '[]';
//                }
//
//            }
//
//        }
        return $frames;
    }


}
