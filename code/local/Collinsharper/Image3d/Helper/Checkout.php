<?php

class Collinsharper_Image3d_Helper_Checkout extends Mage_Core_Helper_Data
{

    function getItemReelUrl($reel = false, $_options = false)
    {
        $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . '/' . 'reel_builder_templates/1x1.png';
        if(!$reel && $_options) {
            $reel = $this->getItemReel($_options);
        }

        if($reel && $reel->getThumb()) {
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . '/' . $reel->getThumb();
        }
        return $url;
    }

    function getItemReel($_options)
    {

        $reel = Mage::getModel('chreels/reels');


        if(!empty($_options)) {
            // cart is a different structure
            if(isset($_options['options'])) {
                $_options = $_options['options'];
            }

            mage::log(__METHOD__ . __LINE__ . " wehafve options " . print_r($_options,1) );


            foreach($_options as $id => $value) {

                if(is_array($value) && isset($value['option_id'])) {
                    $id = $value['option_id'];
                    $value = isset($value['id_value']) ? $value['id_value'] : $value['value'];
                }

                $reelId = (int)(Collinsharper_Image3d_Model_Observer::REELNAMEOPTIONID == $id ? $value : false);

                if(!$reelId && Collinsharper_Image3d_Model_Observer::VIEWER_REELNAMEOPTIONID == $id && is_numeric($value)) {

                    $reelId = (int)$value;
                }

                if(!$reelId && Collinsharper_Image3d_Model_Observer::IMPRINT_REELNAMEOPTIONID == $id && is_numeric($value)) {

                    $reelId = (int)$value;
                }

                if(!$reelId && is_array($value) && isset($value['label']) && $value['label'] == 'Reel ID') {
                    $reelId = (int)$value['value'];
                }

                mage::log(__METHOD__ . __LINE__ . " we found rid  " . print_r($reelId,1) );

                if($reelId) {
                    $reel = Mage::getModel('chreels/reels')->load($reelId);
                }
            }
        }
        return $reel;
    }
}
