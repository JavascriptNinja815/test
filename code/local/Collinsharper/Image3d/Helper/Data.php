<?php

class Collinsharper_Image3d_Helper_Data extends Mage_Core_Helper_Data
{

    CONST PHANTOM_TEMPLATE_FILE = '/media/reel_builder_templates/stripped_down_phantom_template.html';

    const DEFAULT_IMAGE = 'frame0-default.jpg';
    const MISSING_CANVAS_DATA_SLEEP = 2;
    const LOG_FILE = 'ch_frame_generation_debug.log';

    function isServerCallback()
    {
        $serverIpAddress = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR']: false;
        $clientIpAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR']: false;


        $isServerSession = Mage::getSingleton('core/session')->getIsServerCallBack();
        $serverCallbackSkipAuth = $isServerSession || (isset($_GET['process_frame_image']) && $_GET['process_frame_image'] == 1 && $serverIpAddress && $clientIpAddress && $serverIpAddress == $clientIpAddress);
        $serverCallbackSkipAuth = $isServerSession || (isset($_GET['process_frame_image']) && $_GET['process_frame_image'] == 1);


        return $serverCallbackSkipAuth;
    }

    public function updateReportSkipOrderTags($collection, $orderField)
    {
        $tagIds = Mage::getStoreConfig('sales/reporting/reporting_tags_skip');
        $arr = explode(",", $tagIds);
        if(count($arr)) {
            $collection->getSelect()->where("{$orderField} not in (select order_id from aw_ordertags_ot where tag_id in ({$tagIds}))");
        }
    }

    public function getPaymentMethodOptionArray($removeAuthorizeNet=false)
    {
        $options = array(
            // credit cards
            'VI'                => 'Visa',
            'MC'                => 'MasterCard',
            'AE'                => 'American Express',
            'DI'                => 'Discover',

            // authorizenet
            'authorizenet'      => 'Authorize.Net',

            // paypal
            'paypal_express'    => 'PayPal Express',
            'paypal_direct'     => 'PayPal Direct',

            // everything else
            'checkmo'           => 'Check / Money Order',
            'purchaseorder'     => 'Purchase Order',
            'banktransfer'      => 'Bank Transfer',
            'cashondelivery'    => 'Cash On Delivery',
            'free'              => 'Free',
            'giftcard'          => 'Gift Card'
        );

        if ($removeAuthorizeNet) {
            unset($options['authorizenet']);
        }

        return $options;
    }

    function getBaseImageData($frame)
    {
        $image = $frame->getData('source_file');
        $fullFile = BP . DS . 'media' . DS . $image;

        $extension = strpos('png', strtolower($image)) ? 'png' : 'jpg';
        $imgbinary = file_get_contents($fullFile);
        $maxLoops = 3;
        $sleepTime = 4;
        $fails = -1;
        while( $fails < $maxLoops && (!$imgbinary || !strlen($imgbinary))) {
            $fails++;
            mage::logException(new Exception(" Empty file we sleep for {$sleepTime} seconds $fullFile"));
            sleep($sleepTime);
            $frame = Mage::getModel('chframes/frames');
            $frame->load($frame->getEntityId());
            $image = $frame->getData('source_file');
            $fullFile = BP . DS . 'media' . DS . $image;
            $imgbinary = file_get_contents($fullFile);
        }
        if((!$imgbinary || !strlen($imgbinary))) {
            throw new Exception(" cannot render empty file?!?");
        }

        $img_str = base64_encode($imgbinary);
        return "data:image/{$extension};base64,{$img_str}";
    }


    function isFrameBlank($frame, $canSleep = true)
    {
        if(!$frame->getData('source_file') || !is_file(BP . DS . 'media' . DS . $frame->getData('source_file')) || strstr($frame->getData('source_file'), self::DEFAULT_IMAGE)) {
            // mage::logException(new Exception("no file - nothing to render ".print_r($frame->getData(),1)));
            return true;
        }

        // TODO if we have no canvas data; it was a premature save; we cant do anything yet; dont queue it
        //shaneJson.frame_data.canvas_json == undefined || shaneJson.frame_data.canvas_json.objects[0] == undefined
        $testJson = @json_decode($frame->getData('frame_data'));

        $waitForCanvasData = false;

        $this->log(__METHOD__ . " testing json for frame " . json_encode($frame->getData()));

        if(!$frame->getData('frame_data') || !$testJson || !is_object($testJson) || !$testJson->canvas_json) {
            $this->log(__METHOD__ . " no valid json data sleeping " );
            if($canSleep) {
                sleep(self::MISSING_CANVAS_DATA_SLEEP);
                $frame = Mage::getModel('chframes/frames');
                $frame->load($frame->getData('entity_id'));
            }
            if(!$frame->getData('frame_data') || !$testJson || !is_object($testJson) || !$testJson->canvas_json) {
                $this->log(__METHOD__ . " STILL no valid json data  " );
                return true;

            } else {
                $this->log(__METHOD__ . " frame_data sleep seemed to help" );
            }

        }

        $testJson->canvas_json = @json_decode($testJson->canvas_json);

        if(!$testJson->canvas_json || !$testJson->canvas_json->objects || !count($testJson->canvas_json->objects)) {
            $this->log(__METHOD__ . " no valid objects to render sleeping ");
            if($canSleep) {
                sleep(self::MISSING_CANVAS_DATA_SLEEP);
                $frame = Mage::getModel('chframes/frames');
                $frame->load($frame->getData('entity_id'));
            }
            if(!$testJson->canvas_json || !$testJson->canvas_json->objects || !count($testJson->canvas_json->objects)) {
                $this->log(__METHOD__ . " STILL no valid objects to render ");
                return true;
            } else {
                $this->log(__METHOD__ . " canvas objects sleep seemed to help" );
            }
        }

        return false;
    }

    function queueFrameForImageProcessing($reelId, $frameId, $customerId, $frame)
    {
        if(!is_object($frame)) {
            // mage::logException(new Exception("missing frame data !"));
            $frame = Mage::getModel('chframes/frames');
            $frame->load($frameId);
        }

/*
        // TODO we might have to do somethign different here to ensure we have an image?
	//SMD 8-7-2016 Not needed.  Frame will always have a least a default background.
        if($this->isFrameBlank($frame)) {
            return;
        }

*/


        $templateData = file_get_contents(BP . self::PHANTOM_TEMPLATE_FILE);
        $templateData = str_replace('@@BASE_PATH@@', 'file://' . BP . DS, $templateData);
        //$templateData = str_replace('@@BASE64_IMAGE_DATA@@', $this->getBaseImageData($frame->getData('source_file')), $templateData);
        //$templateData = str_replace('@@BASE64_IMAGE_DATA@@', $this->getBaseImageData($frame), $templateData);

        $imagePath =  $image = $frame->getData('source_file');
        $fullFile = 'file://' . BP . DS . 'media' . DS . $image;
        $templateData = str_replace('@@BASE64_IMAGE_DATA@@', $fullFile, $templateData);

        $templateData = str_replace('@@JSON_FRAME_DATA@@', json_encode($frame->getData()), $templateData);
        $filePath = 'tmp/inbound_request/' . $reelId . '_' . $frameId . '_' . $customerId . '_' . rand(999, 99915).'.html';

        $this->log(__METHOD__ . __LINE__ . " writting $filePath with " .strlen($templateData));

        file_put_contents(BP . DS .  'reelbuilderCb/phantomjs/' . $filePath, $templateData);
    }


    function log($x)
    {
        mage::log($x, null, self::LOG_FILE, true);
    }
}
