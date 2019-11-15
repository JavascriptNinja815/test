<?php


class Collinsharper_Image3d_Model_Reelajaxhandler extends Collinsharper_Image3d_Model_Abstract
{

    private $_valid_ajax_methods = array('load_reel', 'save_reel',  'init_reel', 'delete_reel', 'save_image', 'complete_reel', 'generate_reel', 'reel_status', 'save_text');
    private $_valid_reel_save_fields = array('reel_data', 'reel_name');
//    private $_valid_frame_save_fields = array('reel_id', 'frame_index', 'source_file', 'reel_name', 'frame_data', 'thumb_file', 'background_file', 'text_file');
    private $_valid_frame_save_fields = array('frame_index', 'frame_data'); // 'reel_id',
    private $_required_method_params = array(
        'load_reel' => array('reel_id' => false),
        'save_reel'  => array('reel_name' => false, 'customer_id' => 'Requires login'),
        'init_reel'  => array('customer_id' => 'Requires login'),
        'delete_reel' => array('reel_id' => false),
        'save_text' => array('reel_id' => false, 'frame_id' => false, 'text' => false),
        'save_frame' => array('customer_id' => 'Requires login'),
        // 'save_frame' => array('reel_id' => false, 'customer_id' => 'Requires login'),
        'load_frame' => array('reel_id' => false), // we do testing for this elsewhere..
        'delete_frame'  => array('frame_id' => false),
        'save_image' => array('image' => false),
        'complete_reel' => array('reel_id' => false),
        'generate_reel' => array('reel_id' => false),
        'reel_status' => array('reel_id' => false),
    );
    private $_reset_frame_files_list = array(
        'text_file',
        'background_file',
        'viewport_file',
        'left_file',
        'right_file',
        'rendered_file',
        'thumb_file'
    );

    private $_request_data;
    private $_image_help;
    private $_reel_id = false;
    private $_log_file = 'ch_ajax_handler.log';
    private $_log_file_image = 'ch_ajax_image_handler.log';
    private $_updatedText = false;

    const FRAME_TEXT_LAYER_PNG_KEY = 'text_file';
    const FRAME_IMAGE_LAYER_PNG_KEY = 'background_file';
    const LOGIN_REQUIRED = 2;
    const CACHE_LIFE = 1800; // 60 * 30
    const HTTP_CACHE_TIME = 86400;
    const OLD_FRAME_TIME = 120; // 2 minutes means the frame is stale and we regen?
    const MIN_REEL_FRAMES = 8;
    const MAX_WAIT_LOOPS = 9;
    const GENERATION_FRAME_WAIT = 5;
    const IMAGE_REQUEST = 'i3d_image';
    const ONE_PIXEL_PNG = '/media/reel_builder_templates/1x1.png';

    public function getRequest()
    {
        return Mage::app()->getRequest();
    }

    public function getAction()
    {
        return Mage::app()->getRequest();
    }

    public function getSession()
    {
        return Mage::getSingleton('core/session');
    }

    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function getCustomerId()
    {
        return $this->getCustomerSession() && $this->getCustomerSession()->getCustomer() ? $this->getCustomerSession()->getCustomer()->getId() : false;
    }

    protected function _returnResponse($message, $success = false, $data = false)
    {
        if(!$success) {
            try {
                $rbExceptionText = "Error in RB callback: " . print_r($message,1) ."\n";
                $rbExceptionText .= "Data: " . print_r($data,1) ."\n";
                $rbExceptionText .= "Request: " . print_r($_REQUEST,1) ."\n";
                $rbExceptionText .= "Server: " . print_r($_SERVER,1) ."\n";
// magento truncates messages...
                file_put_contents(BP . DS . 'var/log/rb_cb_exception.log', date('c') . $rbExceptionText . "\n", FILE_APPEND);

            } catch (Exception $e) {
                mage::logException(new Exception($message));
                mage::logException($e);
            }

        }

        $ret = array(
            'success' => $success,
            'message' => $message,
            'data' => $data
        );

        //   $this->log(__METHOD__ . __LINE__ . "qw got " . print_r($ret,1));

        return  $ret;
    }

    public function processAjaxAction($forcedData  = false)
    {
        //if !isAjax
        // throw a fit.
        // get the action and call a lower function

        // TODO test form key..
        $this->_request_data = false;
        $this->_request_data = new Varien_Object($this->getRequest()->getParams());

        if($forcedData) {
            $this->_request_data = Varien_Object($forcedData);
        }

        if(!$this->_request_data || !$this->_request_data->action) {
	    mage::log(__METHOD__ . "{$this->_request_data->action}", null, 'smd_debug.log', true);
            return $this->_returnResponse('Invalid Request 002');
        }

        if(!in_array($this->_request_data->action, $this->_valid_ajax_methods)) {
            return $this->_returnResponse('Invalid Request 003');
        }

        $this->_request_data->customer_id = $this->getCustomerId();

        // this would be redundant.
        // $action = '_' . preg_replace('/[^a-z0-9\-\_]+/i','', $this->_request_data->action);

        $action = '_' . $this->_request_data->action;
        $fields = $this->_required_method_params[$this->_request_data->action];

        foreach($fields as $f => $message) {
            $test = $this->_getRequestParam($f);
            if(!$message) {
                $message = "Missing required argument {$f}";
            }
            $message .= ' 004';

            // TODO this will fail testing a boolean..
            if(!$test) {
                return $this->_returnResponse($message);
            }
        }

        $hasImageInPost = round(strlen($this->_request_data->source_file)/(1024*1024),4);
        $hasSaveImage = round(strlen($this->_request_data->image)/(1024*1024),4);
        $hasTextInPost = round(strlen($this->_request_data->text)/(1024*1024),4);

        mage::log(__METHOD__ . "CID {$this->_request_data->customer_id} RID {$this->_request_data->reel_id} FID {$this->_request_data->current_frame_entity_id} FIDX {$this->_request_data->current_frame_index} ACTION {$this->_request_data->action} HASSAVEIMAGE {$hasSaveImage} HASIMAGE {$hasImageInPost} HASTEXT {$hasTextInPost} ", null, 'missing_frame_image_trace.log', true);

        return $this->$action();

    }

    public function _hasRequestParam($x)
    {
        // TODO replace with has property?
        return $this->_request_data->$x != '';
    }

    public function _getRequestParamReelId()
    {
	if(isset($_POST['reel_id'])) {
	    return (int)$_POST['reel_id'];
	} else {
            return $this->_getRequestParam('reel_id');
	}
    }

    public function _getRequestParamFrameId()
    {
        return $this->_getRequestParam('frame_id');
    }

    public function _getRequestParamFrameIndex()
    {
        return $this->_getRequestParam('frame_index');
    }

    public function _getRequestParam($x)
    {
        return $this->_request_data->$x;
    }

    public function _getReelObject()
    {
        return Mage::getModel('chreels/reels');
    }

    public function processImageRequest()
    {

        if(isset($_GET[self::IMAGE_REQUEST])) {
            $this->_log_file = $this->_log_file_image;

            $cache = Mage::app()->getCache();
            $basePath =  BP . DS . 'media' . DS;

            $outputImagePath = false;
            $fullImagePath = false;

            $customerSession = mage::getSingleton('customer/session');
            $validImageTypes = array('frame', 'reel');
            // what are the values of this? // DEPRECATED?!
            $frameImage = isset($_GET['frame_image']) ? (int)$_GET['frame_image'] : false;
            $reelId = isset($_GET['reel_id']) ? $_GET['reel_id'] : '';
            $isPublic = isset($_GET['public']) ? $_GET['public'] : '';
            $frameId = isset($_GET['frame_id']) ? $_GET['frame_id'] : '';
            $sourceFileHash = isset($_GET['sf_hash']) ? $_GET['sf_hash'] : '';
            $imageType = isset($_GET[self::IMAGE_REQUEST]) && in_array($_GET[self::IMAGE_REQUEST], $validImageTypes) ? $_GET[self::IMAGE_REQUEST] : false;
            $thumb = isset($_GET['thumb']) && $_GET['thumb'] == true;
            $framePreview = isset($_GET['frame_preview']) && $_GET['frame_preview'] == true;
            $jpg =  isset($_GET['jpg']) && $_GET['jpg'] == true;
            $cacheKey = $customerSession->getData('remote_addr') .
                $customerSession->getData('id') .
                $customerSession->getData('remote_addr') .
                $reelId . $frameId . (int)$thumb . (int)$jpg . $imageType . $sourceFileHash;

            if($imageType) {

                // TODO VERIFY CUSTOMER ID ACCESS TO REEL OR FRAME
                //$outputImagePath = $cache->load($cacheKey);
                // TODO caching wasnt wowrking with reorder...
                $outputImagePath = false;
                //$this->log(__METHOD__ . __LINE__. " we got " . $outputImagePath );

                // try to get the thumb and save it..
                $cacheTags = array("rb_images", 'ch_reel_id' . $reelId, 'ch_frame_id' . $frameId);
                if(!$outputImagePath && $imageType == 'frame' && $thumb) {
                    $frame = Mage::getModel('chframes/frames');
                    $frame->load($frameId);
                    if($frame->getData('thumb_file')) {
                        $outputImagePath = $frame->getData('thumb_file');
                        $cache->save($frame->getData('thumb_file'), $cacheKey, $cacheTags, self::CACHE_LIFE);
                    }

                    //TODO the thumb code in prcoessor makes bad center thumbs.
                    if($frame->getData('frame_index') == 0 && $frame->getData('thumb_file')) {
                        $outputImagePath = $frame->getData('thumb_file');
                    }

                    if(!$outputImagePath && $frame->getData('frame_index') == 0 && $frame->getData('text_file')) {
                        $outputImagePath = $frame->getData('text_file');
                        if($thumb) {
                            $thumbImage = $this->_generateFrameThumb($basePath . $outputImagePath);
                            $this->log(__METHOD__ . __LINE__ . " BAM $thumbImage ");

                            if($thumbImage) {
                                $newImage = $basePath . $outputImagePath . '_thumb';
                                imagejpeg($thumbImage, $newImage);
                                imagedestroy($thumbImage);
                                $outputImagePath = $newImage;
                            }
                            $cache->save($outputImagePath, $cacheKey, array("rb_images"), self::CACHE_LIFE);
                        } else {
                            $cache->save($frame->getData('text_file'), $cacheKey, array("rb_images"), self::CACHE_LIFE);
                        }
                    }

                }


                // high resolution rendered frame....
                // TODO this should be in assmebler and the code should be abstract4ed so we dont duplicate
                if(!$outputImagePath && $imageType == 'frame' && $framePreview) {
                    $frame = Mage::getModel('chframes/frames');
                    $frame->load($frameId);
                    $reelAssembler = Mage::getModel('chimage3d/reelassembly');
                    $localFileSystemPath = BP . DS . 'media' . DS ;
                    // tODO we shouldnt save the previre!?
                    if(!trim($frame->getData('rendered_file'))) {
                        $framePreview = imagecreatetruecolor(Collinsharper_Image3d_Model_Reelassembly::FRAME_WIDTH, Collinsharper_Image3d_Model_Reelassembly::FRAME_HEIGHT);


                        $frameWidth = Collinsharper_Image3d_Model_Reelassembly::FRAME_WIDTH;
                        $frameHeight = Collinsharper_Image3d_Model_Reelassembly::FRAME_HEIGHT;


                        if($frame->getData('background_file')) {
                            $frameImage = $reelAssembler->_create_from_png( $localFileSystemPath . $frame->getData('background_file'));
                            imagecopy($framePreview, $frameImage, 0, 0, 0, 0, $frameWidth, $frameHeight);

                        }

                        if($frame->getData('text_file')) {
                            $frameText = $reelAssembler->_create_from_png( $localFileSystemPath . $frame->getData('text_file'));
                            imagecopy($framePreview, $frameText, 15, 0, 0, 0, $frameWidth, $frameHeight);

                        }

                        $trans_colour = imagecolorallocatealpha($framePreview, 255, 255, 255, 127);
                        imagealphablending($framePreview, true);
                        imagesavealpha($framePreview, true);
                        $localPath =  $frame->getData('source_file') . '_preview_frame';
                        $fullPath = $localFileSystemPath . $localPath;
                        imagejpeg($framePreview, $fullPath, 85);
                        imagedestroy($framePreview);

                        $frame->setData('rendered_file', $localPath);

                    }

                    $outputImagePath = (string)$frame->getData('rendered_file');

                    $cache->save($outputImagePath, $cacheKey, $cacheTags, self::CACHE_LIFE);
                }



                if(!$outputImagePath) {

                    if($imageType == 'reel' && $reelId) {
                        $reel = Mage::getModel('chreels/reels');
                        $reel->load($reelId);
                        $fullImagePath = $outputImagePath = $reel->getData('final_reel_file');
                        if($thumb) {
                            $fullImagePath = $outputImagePath = $reel->getData('thumb');
                        }
                        // TODO $isPublic public_reel_path

                    } else if($imageType == 'frame' && $frameId) {
                        $frame = Mage::getModel('chframes/frames');
                        $frame->load($frameId);
                        $fullImagePath = $outputImagePath = $frame->getData('source_file');
                        if($frameImage == 3) {
                            $outputImagePath = $frame->getData('background_file');
                        } else if ($frameImage == 2) {
                            $outputImagePath = $frame->getData('text_file');
                        }
                    }

                    if($imageType == 'frame' && ($thumb || $jpg)) {

                        $fullImagePath = $outputImagePath;

                        if($thumb) {
                            $outputImagePath  = str_replace('_full.png', '_thumb.png', $outputImagePath);
                        }

                        if($jpg) {
                            $outputImagePath  = str_replace('.png', '.jpg', $outputImagePath);
                        }
                    }

                    if($imageType == 'frame') {

                        if(!file_exists($basePath . $outputImagePath) && file_exists($basePath . $fullImagePath) && $fullImagePath != $outputImagePath) {
                            // generate it.
                            $image = imagecreatefromstring (file_get_contents($basePath . $fullImagePath));
                            if(!$image) {
                                $this->log(__METHOD__ . __LINE__ . " we have no image ! " . $image);
                            }

                            if($thumb) {
                                $image = $this->_generateFrameThumb($image);
                            }

                            if($jpg) {
                                $this->log(__METHOD__ . __LINE__);
                                imagejpeg($image, $basePath . $outputImagePath);
                            } else {
                                $this->log(__METHOD__ . __LINE__);
                                imagepng($image, $basePath . $outputImagePath);
                            }
                            imagedestroy($image);

                        }

                    }
                    if($outputImagePath) {
                        $cache->save($outputImagePath, $cacheKey, $cacheTags, self::CACHE_LIFE);
                    }
                }
            }


            if(!$outputImagePath || $outputImagePath === false || (string)$outputImagePath == "false") {
                header("HTTP/1.0 404 Not Found");
                $outputImagePath = null;
            } else {
                // TODO handle cache
                header('Pragma: public');
                header('Cache-Control: max-age=' . self::HTTP_CACHE_TIME);
                header('Expires: '. gmdate('D, d M Y H:i:s \G\M\T', time() + self::HTTP_CACHE_TIME));

            }


            if($jpg) {
                header('Content-Type: image/jpeg');
            } else {
                header('Content-Type: image/png');
            }

            //$this->log(__METHOD__ . __LINE__ . " out " . ( $basePath . $outputImagePath  ));
            // TODO this needs cleaning up..
            //$imageFile = $outputImagePath ? $basePath . $outputImagePath :  $basePath . Collinsharper_Image3d_Helper_Image::FOUR_OH_FOUR_IMAGE;
            $imageFile = $outputImagePath ? $basePath . $outputImagePath :  $basePath . '/media/reel_builder_templates/1x1.png';
            if(!file_exists($imageFile)) {
                $imageFile = BP . DS . 'media/reel_builder_templates/1x1.png';
            }
            $fp = fopen($imageFile, 'rb');

            header("Content-Length: " . filesize($imageFile));

            // $this->log(__METHOD__ . __LINE__ . " out " . ( $basePath . $outputImagePath  ));
            fpassthru($fp);

        }

    }

    public function processImageUpload()
    {
	if(!isset($_FILES['userfile']['tmp_name'])) {
		mage::log(__METHOD__ . "no image", null, 'smd_debug.log', true);
		return $this->_returnResponse("Failed to upload image  666");
	}

	//Process the posted image
	//May be jpg or png.  Needs to be properly sized for frame or center.
	//Set some variables
	$maxWidth = $newWidth = Collinsharper_Image3d_Model_Reelassembly::FRAME_WIDTH * 2;
	$maxHeight = $newHeight = Collinsharper_Image3d_Model_Reelassembly::FRAME_HEIGHT * 2;
	$fType = $_FILES['userfile']['type'];
	list($imageWidth, $imageHeight) = getimagesize($_FILES['userfile']['tmp_name']);

	if($_POST['frame_index'] != 0 && ($imageWidth > $maxWidth || $imageHeight > $maxHeight)) { //Resize
		if($imageWidth >= $imageHeight) {
			$newHeight = $imageHeight * ($maxWidth/$imageWidth);
		} else {
			$newWidth = $imageWidth * ($maxHeight/$imageHeight);
		}

		$newImage = imagecreatetruecolor($newWidth, $newHeight);
	
		if($fType == 'image/jpeg') {
			$source = imagecreatefromjpeg($_FILES['userfile']['tmp_name']);
		} else {
			$source = imagecreatefrompng($_FILES['userfile']['tmp_name']);
		}

		imagecopyresized($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $imageWidth, $imageHeight);
		ob_start();
		imagejpeg($newImage);
		$contents = ob_get_contents();
		ob_end_clean();
		imagedestroy($source);
		imagedestroy($newImage);
	} else {
		$contents = file_get_contents($_FILES['userfile']['tmp_name']);
	}

	//$imageData = base64_encode(file_get_contents($_FILES['userfile']['tmp_name']));
	$imageData = base64_encode($contents);
	$imageSrc = 'data:image/jpeg;base64,'.$imageData;
	$frame = false;
	$imageHelper = $this->getImageHelper();

	$frame = $this->_getFrameObject()->load($_POST['entity_id']);
	if(!$this->_checkValidFrame($frame)) {
            return $this->_returnResponse("Invalid Frame Identifier 075");
        }

        try {

            $fileName = $this->_handleInboundImage($imageSrc, $frame);
	    $thumb = false;

            if($fileName) {
                $frame->setSourceFile($fileName);
                $frame->setData('rendered_file','');
                $frame->setData('thumb_file','');
                $frame->setData('left_file','');
                $frame->setData('right_file','');
            } else {
                mage::logException(new Exception("failed to update image "));
            }

	    $fieldList = array(
                'source_file',
                'rendered_file',
                'thumb_file',
                'left_file',
                'right_file'
            );

            $this->saveFrameFields($frame, $fieldList);

	    echo $fileName;

        } catch(Exception $e) {
            return $this->_returnResponse("Failed to save image  007 " . $e->getMessage());
        }
    }

    function _generateFrameThumb($image)
    {
        $this->log(__METHOD__ . __LINE__ . " and " );
        if(!is_resource($image) && is_file($image)) {
            $image = imagecreatefromstring(file_get_contents($image));
        }

        if(!is_resource($image)) {
            $this->log(__METHOD__ . __LINE__ . " and " . $image);
        }
        $width = $orig_width = imagesx($image);
        $height = $orig_height = imagesy($image);// = getimagesize($basePath . $fullImagePath);

        $max_height = Collinsharper_Image3d_Helper_Image::THUMB_HEIGHT;
        $max_width = Collinsharper_Image3d_Helper_Image::THUMB_WIDTH;

        // TALLER
        if ($height > $max_height) {
            $width = ($max_height / $height) * $width;
            $height = $max_height;
        }

        // WIDER
        if ($width > $max_width) {
            $height = ($max_width / $width) * $height;
            $width = $max_width;
        }

        $thumb = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0,
            $width, $height, $orig_width, $orig_height);
        imagedestroy($image);
        $image = $thumb;
        unset($thumb);
        return $image;
    }

    public function _getFrameObject()
    {
        return Mage::getModel('chframes/frames');
    }

    private function _checkValidReel($reel)
    {
        return $reel && $reel->getEntityId();
    }

    private function getImageHelper()
    {
        if(!$this->_image_help) {
            $this->_image_help = mage::helper('chimage3d/image');
        }
        return $this->_image_help;
    }

    private function _checkValidFrame($frame)
    {
        return $frame && $frame->getEntityId();
    }

    private function _saveFrameImage($data, $frame)
    {
        $imageHelper = $this->getImageHelper();
        $imageData = $imageHelper->_processInboundBase64Image($data);
        return $imageHelper->saveFrameImage($imageData, $frame);
    }

    private function _updateCenterThumb($frame)
    {
        $file = BP . DS . 'media' . DS . $frame->getData('text_file');

        if(!$frame->getData('text_file') || !file_exists($file)) {
            return false;
        }

        $specificPath = 'thumb' . ($this->getCustomerId() ? DS . $this->getCustomerId() : '');

        $fullPath = $this->getImageHelper()->getReelStoragePath() . DS . $specificPath;

        if(!is_dir($fullPath)) {
            mkdir($fullPath, 0775, true);
        }

        if(!is_dir($fullPath)) {
            mage::logException(new Exception('cannot store files 040 CENT#ER'));
        }

        $image = imagecreatefromjpeg($file);

        $thumbWidth = 200;
        $thumbHeight = 200;
        $actualWidth = imagesx($image);
        $actualHeight = imagesy($image);
        $frame = imagecreatetruecolor($thumbWidth, $thumbHeight);

        imagealphablending($frame, false);
        imagesavealpha($frame, true);
        imagecopyresampled( $frame, $image,
            0, 0,
            0, 0,
            $thumbWidth, $thumbHeight,
            $actualWidth, $actualHeight );

        imagedestroy($image);

        $this->getImageHelper()->log(__METHOD__ . __LINE__ . " path $fullPath spec  $specificPath ");

        $thumb = $this->getImageHelper()->_saveImageData($frame, $this->getImageHelper()->guid() . "_thumb", $fullPath, $specificPath);

        return $thumb;
    }

    private function _updateFrameThumb($frame)
    {
        return $this->getImageHelper()->_updateFrameThumb($frame);
    }

    private function _checkValidReelCustomerPermissions($reel)
    {
        $helper = new Collinsharper_Image3d_Helper_Data;
        $serverCallbackSkipAuth = $helper->isServerCallback();

        if($serverCallbackSkipAuth) {
            return true;
        }

        $cachedReelPerms = $this->getCustomerSession()->getCachedReelPermissions();
        $reelId = is_object($reel) ? $reel->getId() : (int)$reel;

        $this->log(__METHOD__ . __LINE__ . "We have reel Id " . $reelId);

        if(isset($cachedReelPerms[$reelId]) && $cachedReelPerms[$reelId] == true) {
            return true;
        }

        if(!is_object($reel)) {
            $reel = $this->_getReelObject()->load($reelId);
        }
        $this->log(__METHOD__ . __LINE__ . "  " .  $reel->getCustomerId());
        if($this->getCustomerId() && $this->getCustomerId() == $reel->getCustomerId()) {
            $cachedReelPerms[$reelId] = true;
            $this->getCustomerSession()->setCachedReelPermissions($cachedReelPerms);
            return true;
        }
        return false;
    }

    private function _load_reel()
    {
        $reel = $this->_getReelObject()->load($this->_getRequestParamReelId());

        if(!$this->_checkValidReel($reel)) {
            return $this->_returnResponse("Invalid Reel Identifier 005");
        }

        $helper = new Collinsharper_Image3d_Helper_Data;
        $serverCallbackSkipAuth = $helper->isServerCallback();

        if(!$serverCallbackSkipAuth) {


            if(!$this->_checkValidReelCustomerPermissions($reel)) {
                return $this->_returnResponse("Invalid Reel Identifier 006lr");
            }

            if($reel->getStatus() == Collinsharper_Reels_Model_Reels::COMPLETE_STATUS) {
                Mage::getSingleton('customer/session')->addNotice('That reel is complete and cannot be opened in the builder.');
                return $this->_returnResponse("Reel is Complete 009a");
            }
        }

        $reelData = $this->_getAjaxReelData($reel);
        $reelData['frame_data'] =  $reel->getAjaxFrames();

        $framesNeedUpdate = false;
        for($i=0;$i< self::MIN_REEL_FRAMES;$i++) {
            $testFrame = isset($reelData['frame_data'][$i]) ? $reelData['frame_data'][$i] : false;
            // the frames come in order - if we dont have one for each of the initial 8 create them and reload
            if($reelData['frame_data'][$i]['frame_index'] != $i) {
                $framesNeedUpdate = true;
                // create the frame
                $frame = $this->_getFrameObject();
                $frame->setFrameIndex($i);
                $frame->setReelId($reel->getEntityId());
                $frame->save();
            }
        }

        if($framesNeedUpdate) {
            $reelData['frame_data'] =  $reel->getAjaxFrames(true);
        }
        return $this->_returnResponse("success", true, $reelData);

    }

    private function _init_reel()
    {

        if(!$this->getCustomerId()) {
            return $this->_returnResponse("Login required 008", self::LOGIN_REQUIRED);
        }

        $reel = $this->_load_create_reel();

        $this->_init_reel_frames($reel);

        $reelData = $this->_getAjaxReelData($reel);
        $ajaxFrames = $reel->getAjaxFrames(true);
        $reelData['posted_frame_index'] =  1;
        $reelData['posted_frame_entity_id'] =  $ajaxFrames[1];
        $reelData['_action'] = 'init_reel';
        $reelData['frame_data'] = $ajaxFrames;

        return $this->_returnResponse("success", true, $reelData);
    }

    private function _getAjaxReelData($reel)
    {
        $data = array();
        $data['entity_id'] = $reel->getEntityId();
        $data['reel_name'] = $reel->getData('reel_name');
        $data['final_reel_file'] = $reel->getData('final_reel_file');
        return $data;
    }

    private function _save_reel()
    {
        // TODO be transactional about it?
        // $transactionSave = Mage::getModel('core/resource_transaction')

        if(!$this->getCustomerId()) {
            return $this->_returnResponse("Login required 008", self::LOGIN_REQUIRED);
        }

        $reel = $this->_getReelObject();

        $reel->load($this->_getRequestParam('reel_id'));

        if(!$this->_checkValidReel($reel)) {
            return $this->_returnResponse("Invalid Reel Identifier 005");
        }

        if(!$this->_checkValidReelCustomerPermissions($reel)) {
            return $this->_returnResponse("Invalid Reel Identifier 006s");
        }

        if($reel->getStatus() == Collinsharper_Reels_Model_Reels::COMPLETE_STATUS) {
            Mage::getSingleton('customer/session')->addNotice('That reel is complete and cannot be opened in the builder.');
            return $this->_returnResponse("Reel is Complete 009a");
        }

        $this->_reel_id = $reel->getId();

        try {

            $update = false;
            foreach($this->_valid_reel_save_fields as $f) {
                if($this->_getRequestParam($f)) {
                    $update = true;
                    $reel->setData($f, $this->_getRequestParam($f));
                }
            }

            $reel->save();


        } catch (Exception $e) {
            return $this->_returnResponse("Failed to save REEL 007", false, $e->getMessage());
        }

        // DEBUG
        // DEBUG
        $this->log(__METHOD__ . __LINE__  );
        //$this->log(__METHOD__ . __LINE__  . " posted data " . print_r($_POST,1));

        //Save the text directly
        $frameTextData = $this->_getRequestParam('text');

        if($frameTextData) {
            $this->_updatedText = true;
            $frame = $this->_getFrameObject();
            $frame->load($this->_getRequestParam('current_frame_entity_id'));
            //$fileName = $this->_handleInboundImage($frameTextData, $frame);
            $imageHelper = $this->getImageHelper();

            $specificPath = $reel->getCustomerId() ? DS . $reel->getCustomerId() : '';

            if($frame && $frame->getReelId()) {
                $specificPath  .= DS . $frame->getReelId();
            }

            $fullPath = $imageHelper->getReelStoragePath() . DS . $specificPath;

            if(!is_dir($fullPath)) {
                mkdir($fullPath, 0775, true);
            }

            if(!is_dir($fullPath)) {
                mage::throwException('cannot store files 040');
            }

            $imageName = $imageHelper->guid();
            $saveName = $imageHelper->_generateImageName($imageName, '_full', '.png');

            //Decode the frameTextData
            $processedTextData = $imageHelper->_processInboundBase64Image($frameTextData);
            $imageData = base64_decode($processedTextData);

            $verifyPngImage = @imagecreatefromstring($imageData);
            if($verifyPngImage === false) {
                mage::logException(new Exception("text image is not PNG "));
            }
            @imagedestroy($verifyPngImage);

            $savePath = $fullPath . '/' . $saveName;
            file_put_contents($savePath, $imageData);

            //Trim the savePath for the database
            $textDbName = substr($savePath, strpos($savePath, 'reel_builder'));

            if($saveName) {
                $frame->setTextFile($textDbName);
            } else {
                mage::logException(new Exception("failed to  update text image "));
            }

            $fieldList = array(
                'text_file',
            );

            $this->saveFrameFields($frame, $fieldList);
        } else {
            $frame = $this->_getFrameObject();
            $frame->load($this->_getRequestParam('current_frame_entity_id'));
            if($frame->getTextFile()){
                $frame->setTextFile('');
                $fieldList = array(
                    'text_file',
                );

                $this->saveFrameFields($frame, $fieldList);
            }
        }

        $frameDataInbound = $this->_getRequestParam('frame_data');
        $frameObjects = array();

        // first pass load all the frames and ensure we have access
        foreach($frameDataInbound as $k => $frameData) {

            $frame = $this->_getFrameObject();
            $frame->load($frameData['entity_id']);
            if(!$frame->getEntityId() || $frame->getReelId() != $reel->getId()) {
                return $this->_returnResponse("Failed to save REEL 009", false);
            }
            $frameObjects[$k] = $frame;
        }

        $frame = false;

        foreach($frameDataInbound as $k => $frameData) {

            $frame = $frameObjects[$k];
            if(!$this->_updateFrameObject($reel, $frame, $frameData)) {
                mage::logException(new Exception("could not update frame"));
                return $this->_returnResponse("Failed to save REEL 011a", false);
            }

        }


        $reelData = $this->_getAjaxReelData($reel);
        $ajaxFrames = $reel->getAjaxFrames(true);
        $reelData['posted_frame_index'] =  $this->_getRequestParam('current_frame_index');
        $reelData['posted_frame_entity_id'] =  $this->_getRequestParam('current_frame_entity_id');
        $reelData['_action'] = 'save_reel';
        $reelData['frame_data'] = $ajaxFrames;

        $this->log(__METHOD__ . __LINE__  . " RET data " . print_r($reelData,1));

        if(1) {
            return $this->_returnResponse("success", true, $reelData);
        }

        // DEBUG

        $reelData = $this->_getAjaxReelData($reel);
        $ajaxFrames = $reel->getAjaxFrames(true);
        $reelData['posted_frame_index'] =  $this->_getRequestParam('current_frame_index');
        $reelData['posted_frame_entity_id'] =  $this->_getRequestParam('current_frame_entity_id');
        $reelData['_action'] = 'save_reel';
        $reelData['frame_data'] = $ajaxFrames;

        return $this->_returnResponse("success", true, $reelData);

    }

    private function _save_text()
    {
        $frame = false;
        $imageHelper = $this->getImageHelper();

        if((int)$this->_getRequestParamReelId() > 0) {
            if(!$this->_checkValidReelCustomerPermissions($this->_getRequestParamReelId())) {
                return $this->_returnResponse("Invalid Reel Identifier 076");
            }
        }

        //we always use a frame to save a text image Or we create one.
        if($this->_getRequestParamFrameId() > 0) {
            $frame = $this->_getFrameObject()->load($this->_getRequestParamFrameId());

            if(!$this->_checkValidFrame($frame)) {
                return $this->_returnResponse("Invalid Frame Identifier 075");
            }

        }

        $reel = $this->_load_create_reel();

        $frame = $this->_load_create_frame();


        try {

            $fileName = $this->_handleInboundImage($this->_getRequestParam('text'), $frame);

            if($fileName) {
                $frame->setTextFile($fileName);
            } else {
                mage::logException(new Exception("failed to  update text image "));
            }

            $fieldList = array(
                'text_file',
            );

            $this->saveFrameFields($frame, $fieldList);

            $reelData = $this->_getAjaxReelData($reel);

            return $this->_returnResponse("success", true, $reelData);

        } catch(Exception $e) {
            return $this->_returnResponse("Failed to save image  007 " . $e->getMessage());
        }
    }

    private function _complete_reel()
    {
        $reel = $this->_getReelObject();

        if(!$this->getCustomerId()) {
            return $this->_returnResponse("Login required 008", self::LOGIN_REQUIRED);
        }

        if($this->_getRequestParam('reel_id')) {

            $reel->load($this->_getRequestParam('reel_id'));

            if(!$this->_checkValidReel($reel)) {
                return $this->_returnResponse("Invalid Reel Identifier 005");
            }

            if(!$this->_checkValidReelCustomerPermissions($reel)) {
                return $this->_returnResponse("Invalid Reel Identifier 006c");
            }
        } else {

            return $this->_returnResponse("Invalid Reel Identifier 006a");

        }

        $reel->setData('status', Collinsharper_Reels_Model_Reels::COMPLETE_STATUS);
        $reel->save();
        // NOTE: we do not delete frame data until the 90 days.
        //    $reel->deleteFrameData();
        return $this->_returnResponse("success", true, array('reel_id' =>  $reel->getId()));

    }

    private function _reel_status()
    {
        date_default_timezone_set('America/Los_Angeles');


        $reel = $this->_getReelObject();

        if(!$this->getCustomerId()) {
            return $this->_returnResponse("Login required 008", self::LOGIN_REQUIRED);
        }

        if($this->_getRequestParam('reel_id')) {

            $reel->load($this->_getRequestParam('reel_id'));

            if(!$this->_checkValidReel($reel)) {
                return $this->_returnResponse("Invalid Reel 005", true);
            }

            if(!$this->_checkValidReelCustomerPermissions($reel)) {
                return $this->_returnResponse("Invalid Reel 006g", true);
            }

        } else {

            return $this->_returnResponse("Invalid Reel 006gb", true);

        }

        $frames = $reel->getFrames(true);
        if(count($frames) < self::MIN_REEL_FRAMES) {
            return $this->_returnResponse("Reel is Missing Frames 001", true);
        }


        $mediaPath = BP . DS . 'media' . DS;

        foreach($frames as $index => $frameObj) {
            $name = "Frame " . $frameObj->getData('frame_index');
            $frame = $frameObj->getData();
            if($frame['frame_index'] == 0) {
                //$name = "Center Frame";
                $name = Mage::app()->getLayout()->createBlock('cms/block')->setBlockId('center_frame_generation')->toHtml();
            }

            $centerIsValid = $this->_centerFrameIsValid($frame);
            $frameIsValid = $this->_frameIsValid($frame);

            if(!$centerIsValid || !$frameIsValid) {
                return $this->_returnResponse($name . " is still being generated.", true);
            }

        }


        if(!$reel->getData('final_reel_file')) {
            return $this->_returnResponse("Reel is still being generated", true);

        }

        if($reel->getData('final_reel_file')) {
            $list = array(
                $this->getImageHelper()->__('Your reel is almost complete.'),
                $this->getImageHelper()->__('You blink about 12 times every minute.'),
                $this->getImageHelper()->__('Have you thought about getting extra copies for friends?'),
                $this->getImageHelper()->__('Did you know the human eye can see 7,000,000 different colors?'),
                $this->getImageHelper()->__('The human eye and brain can interpret up to 1000 frames per second.'),
                $this->getImageHelper()->__('Humans are known as trichromats, meaning they have three kinds of cones in their eyes that allow them to see red, green, and blue.'),
                $this->getImageHelper()->__('The average blink lasts for about 1/10th of a second.'),
                $this->getImageHelper()->__('Some people are born with two differently colored eyes. This condition is called heterochromia.'),
                $this->getImageHelper()->__('Out of all the muscles in your body, the muscles that control your eyes are the most active.'),
            );
            return $this->_returnResponse($list[array_rand($list)], true);
        }

        return $this->_returnResponse("Not quiet ready yet!", true);

    }

    private function _centerFrameIsValid($frame)
    {
        $mediaPath = BP . DS . 'media' . DS;
        if($frame['frame_index'] != 0) {
            return true;
        }

        return $frame['frame_index'] == 0 &&
        (isset($frame['rendered_file']) &&
            $frame['rendered_file'] != '' &&
            is_file($mediaPath . $frame['rendered_file']));
    }

    private function _frameIsValid($frame)
    {
        $mediaPath = BP . DS . 'media' . DS;
        if($frame['frame_index'] == 0) {
            return true;
        }

        return $frame['frame_index'] != 0 &&
        (isset($frame['left_file']) && $frame['left_file'] != '' && is_file($mediaPath . $frame['left_file'])) &&
        (isset($frame['right_file']) && $frame['right_file'] != '' && is_file($mediaPath . $frame['right_file']));
    }

    // preview_reel
    private function _generate_reel()
    {
        $reel = $this->_getReelObject();

        if(!$this->getCustomerId()) {
            return $this->_returnResponse("Login required 008", self::LOGIN_REQUIRED);
        }

        if($this->_getRequestParam('reel_id')) {

            $reel->load($this->_getRequestParam('reel_id'));

            if(!$this->_checkValidReel($reel)) {
                return $this->_returnResponse("Invalid Reel Identifier 005");
            }

            if(!$this->_checkValidReelCustomerPermissions($reel)) {
                return $this->_returnResponse("Invalid Reel Identifier 006g");
            }
        } else {

            return $this->_returnResponse("Invalid Reel Identifier 006gb");

        }


        try {
            $frames = $reel->getFrames(true);

            if(count($frames) < 8) {
                return $this->_returnResponse("Reel is Missing Frames 001g");
            }


            $mediaPath = BP . DS . 'media' . DS;
            foreach($frames as $index => $frameObj) {
                $name = "Frame " . $frameObj->getData('frame_index');
                $frame = $frameObj->getData();
                if($frame['frame_index'] == 0) {
                    $name = "Center Frame";

                }


                $isCenterAndEmpty = $frameObj->getData('frame_index') == 0 && mage::helper('chimage3d')->isFrameBlank($frameObj, false);

                $centerIsValid = $isCenterAndEmpty || $this->_centerFrameIsValid($frame);
                $frameIsValid = $this->_frameIsValid($frame);

                if(!$centerIsValid || !$frameIsValid) {

                    // we have an invalid frame. we need to give it 10 seconds to see if it clears up?
                    $frameIsOld = !isset($frame['updated_at']) || strtotime($frame['updated_at']) - strtotime("now") > self::OLD_FRAME_TIME;
                    // if its old? what then?
                    if($frameIsOld) {
                        return $this->_returnResponse("Please re save {$name}");
                    }
                    // if we have an error; we just sleep; then check them all again.
                    sleep(self::GENERATION_FRAME_WAIT);
                    // get the items we need to check for or launch the process
                    $rid = $frame['reel_id'];
                    $fid = $frame['entity_id'];
                    $cid = $reel['customer_id'];

                    $isCenterAndEmpty = false;

                    //See if a generation process is running
                    // TODO maybe exec is disabled on nexcess we can use ob_start end_clean and passthru
                    $strPs = 'ps aux | grep -v grep | grep ' . '"'.$rid . ' ' . $fid . ' ' . $cid.'"';

                    ob_start();
                    passthru($strPs);
                    $output = trim(ob_get_contents());
                    ob_end_clean();

                    // NOTE: the issue here is we allow center to be blank ; refer to ticket #607 for details
                    // no process we queue it once
                    if(strstr($output, 'generateFrameImages') === false) { //No process
                        $this->log(__METHOD__ . __LINE__ . " forcing regen $rid, $fid, $cid " . $frameObj->getData('frame_index'));

                        if($frameObj->getData('frame_index') == 0) {
                            $isCenterAndEmpty = mage::helper('chimage3d')->isFrameBlank($frameObj, false);
                        }

                        if($frameObj->getData('frame_index') != 0 || ($frameObj->getData('frame_index') == 0 && !$isCenterAndEmpty)) {
                            mage::helper('chimage3d')->queueFrameForImageProcessing($rid, $fid, $cid, $frameObj);
                            sleep(self::GENERATION_FRAME_WAIT);
                        }

                    }


                    // if we dont think we have a valid center it WASNT already generating; we dont need to launch it or wait for it.
                    $centerIsValid = $centerIsValid || $isCenterAndEmpty;

                    $sleepIndex = 0;
                    $frame =  (array)$this->_getFrameObject()->load($frame['entity_id'])->getData();
                    $centerIsValid = $this->_centerFrameIsValid($frame);
                    $frameIsValid = $this->_frameIsValid($frame);
                    while((!$centerIsValid || !$frameIsValid) && $sleepIndex < self::MAX_WAIT_LOOPS) {
                        $sleepIndex++;
                        sleep(self::GENERATION_FRAME_WAIT);
                        $frame =  (array)$this->_getFrameObject()->load($frame['entity_id'])->getData();
                        $centerIsValid = $this->_centerFrameIsValid($frame);
                        $frameIsValid = $this->_frameIsValid($frame);
                    }


                    //Check again to see if the frames are all good.
                    $centerIsValid = $this->_centerFrameIsValid($frame) || $isCenterAndEmpty;
                    $frameIsValid = $this->_frameIsValid($frame);

                    if(!$centerIsValid || !$frameIsValid) {
                        return $this->_returnResponse("Please re save {$name}");
                    }

                }

            }


            $path = "reel_builder/complete/{$reel->getCustomerId()}/";
            $localFileSystemPath = BP . DS . 'media' . DS;
            $fileName = "{$reel->getId()}.jpg";
            $fileNameThumb = "{$reel->getId()}_thumb.jpg";
            $fullLocalPathFile = $localFileSystemPath . $path . $fileName;
            $fullLocalPathThumb = $localFileSystemPath . $path . $fileNameThumb;
            $this->log(__METHOD__ . " forced generation NOWE ");
            ob_start();
            // TODO we need to check all updaetd time stamps; if the reel generation time was after we last changed anything we do not need to regenerate it.

            if(1 || !$reel->getData('final_reel_file') || !file_exists($fullLocalPathFile)) {
                $this->log(__METHOD__ . " forced generation");

                /** @var Collinsharper_Image3d_Model_Reelassembly $reelAssembler */
                $reelAssembler = Mage::getModel('chimage3d/reelassembly');

                $this->log(__METHOD__ . " now  forced generation");
                $finalImage = $reelAssembler->buildReelByReelId($reel->getId());
                $this->log(__METHOD__ . " done forced generation");
                //   $fullLocalPathFile = $reelAssembler->generateFinalThumbRotateReel($finalImage, $localFileSystemPath, $localFileSystemPath, $path);

                $fullLocalPathFile = $reelAssembler->generateFinalThumbRotateReel($finalImage, $fullLocalPathFile, $localFileSystemPath, $path, $fullLocalPathThumb);

//                $fullLocalPathFile = $reelAssembler->generateFinalThumbRotateReel($finalImage, $fullLocalPathFile, $localFileSystemPath, $path, $fullLocalPathFile, $fullLocalPathThumb);

                $reel->setData('final_reel_file', $path . $fileName);
                $this->log(__METHOD__ . " saving file reel file   $path . $fileName ");
                $reel->setData('thumb', $path . $fileNameThumb);

                $reel->save();
                $this->_reel_id = $reel->getId();

            }

            if(!$reel->getData('thumb') || !file_exists( $fullLocalPathThumb)) {
                $finalImage = imagecreatefromjpeg ($fullLocalPathFile);
                $imageScale = .25;
                $srcWidth = imagesx($finalImage);
                $srcHeight = imagesy($finalImage);
                $newWidth = imagesx($finalImage)*$imageScale;
                $newHeight = imagesy($finalImage)*$imageScale;


                $thumbResource = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($thumbResource, $finalImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
                imagedestroy($finalImage);
                imagejpeg($thumbResource, $fullLocalPathThumb, 75);
                imagedestroy($thumbResource);
                //    $reel->setData('thumb', $path . $fileNameThumb);
            }

            // one of these are making a mess
            ob_end_clean();

        } catch (Exception $e) {
            return $this->_returnResponse("Failed to geenrate REEL 056", false, $e->getMessage());
        }

        // one of these are making a mess
        ob_end_clean();



        return $this->_returnResponse("success", true, array('reel_id' =>  $reel->getId(), 'success' =>  $path . $fileNameThumb));

    }

    private function _delete_reel()
    {
        throw new Exception(" deprecated function");

        $reel = $this->_getReelObject()->load($this->_getRequestParamReelId());

        if(!$this->_checkValidReel($reel)) {
            return $this->_returnResponse("Invalid Reel Identifier 005");
        }

        if(!$this->_checkValidReelCustomerPermissions($reel)) {
            return $this->_returnResponse("Invalid Reel Identifier 006d");
        }

        $reel->delete();

        return $this->_returnResponse("success", true);

    }

    function verifyCustomerLoggedIn($isAjax = false)
    {

        $helper = new Collinsharper_Image3d_Helper_Data;
        $serverCallbackSkipAuth = $helper->isServerCallback();

        if($serverCallbackSkipAuth) {
            return true;
        }

        $customer_session = Mage::getSingleton ( 'customer/session', array ('name' => 'frontend' ) );

        if(!Mage::helper('customer')->isLoggedIn()) {
            $customer_session->addError(Mage::helper('customer')->__('Please login to modify your reel.'));
            $jsonReturn = array(
                'success' => false,
                'login_redirect' => Mage::getUrl('customer/account/login', array('referer' => Mage::helper('core')->urlEncode($this->getUrl("/") . 'reelbuilder/'))),
                'message' => Mage::helper('customer')->__('Please login')
            );

        }

    }


    private function _init_reel_frames($reel)
    {
	//Create the reel directory and copy default backgrounds into it
	$dirBase = BP . DS . 'media' . DS;
	$reelDir = 'reel_builder' . DS . 'uploads' . DS . 'users' . DS . $reel->getCustomerId() . DS . $reel->getId();
	$dirName = $dirBase . $reelDir;
	mkdir($dirName, 0775, true);
        for($i = 0; $i < self::MIN_REEL_FRAMES; $i++) {
            $source = $dirBase . DS . 'reel_builder_templates' . DS . 'frame'.$i.'-default.jpg';
	    $dest = $dirName . DS . 'frame'.$i.'-default.jpg';
	    copy($source, $dest);
            $frame = $this->_getFrameObject();
            $frame->setFrameIndex($i);
            $frame->setReelId($reel->getEntityId());
	    //Set a default background so just text is possible.
	    //$frame->setSourceFile('reel_builder_templates/center-default.jpg');
	    $frame->setSourceFile($reelDir . DS . 'frame'.$i.'-default.jpg');
	  
            $frame->save();
        }
    }

    private function _load_create_reel()
    {
        $reel = $this->_getReelObject();
        if((int)$this->_getRequestParam('reel_id') <= 0) {
            $this->getImageHelper()->log(__METHOD__ . __LINE__);
            $this->reel_id = false;
            $reel->setCustomerId($this->getCustomerId());
            $reel->setReelName($this->_getRequestParam('reel_name'));
            $reel->save();
            $this->_request_data->reel_id = $this->reel_id = $reel->getEntityId();

            $this->getImageHelper()->log(__METHOD__ . __LINE__ . " REEL ID " . $this->reel_id );

        } else {
            $this->reel_id = (int)$this->_getRequestParamReelId();
            $reel->load($this->reel_id);
        }
        return $reel;
    }

    private function _load_create_frame($frameId = false)
    {
        $this->getImageHelper()->log(__METHOD__ . __LINE__ );
        $frame = $this->_getFrameObject();
        if(!$frameId && $this->_getRequestParamFrameId() > 0) {
            $frameId = (int)$this->_getRequestParamFrameId();
        }

        if(!$frameId) {
            $this->getImageHelper()->log(__METHOD__ . __LINE__);
            $frame->setReelId($this->reel_id);
            $frame->setFrameIndex($this->_getRequestParam('frame_index'));
            $frame->save();

            $this->getImageHelper()->log(__METHOD__ . __LINE__ . " FRAME ID " .  $frame->getEntityId() );

        } else {
            $frame->load($frameId);
        }
        $this->log(__METHOD__ . __LINE__ );

        return $frame;
    }

    private function saveFrameFields($frame, $fieldList)
    {
        // we have to do it with SQL
        $table = Mage::getSingleton("core/resource")->getTablename("ch_frames");
        $write = Mage::getSingleton("core/resource")->getConnection("core_write");



        $sql = "update {$table} set ";
        $binds = array();
        foreach($fieldList as $field) {
            $sql .= "{$field} = :$field,";
            $value = $frame->getData($field);
            if(is_array($value)) {
                $value = json_encode($value);
            }

            $binds[$field] = $value;

        }

        $sql = rtrim($sql, ",");

        $sql .= " where entity_id = " . $frame->getData('entity_id');

        $write->query($sql, $binds);

    }


    private function _updateFrameObject($reel, $frame, $frameData)
    {
        try {

            $oldFrameData = clone $frame;

            foreach($this->_valid_frame_save_fields  as $f) {

                if(isset($frameData[$f])) {
                    $update = true;
                    $value = $frameData[$f];
                    if(is_array($value)) {
                        $value = json_encode($value);
                    }
                    $frame->setData($f, $value);
                }

            }

            // TODO we should remove the files as well..


            $fieldList = array(
                'frame_data',
                'frame_index',
                'reel_id',
            );

            $this->saveFrameFields($frame, $fieldList);


            if($this->_getRequestParam('current_frame_index') == $frame->getData('frame_index')) {

                //  if($oldFrameData->getData('frame_data') == $frame->getData('frame_data') && !$this->_updatedText) {
                //      $this->log(__METHOD__ . __LINE__  . " SHANE we dont need to REGEN - it is the same " . $frame->getData('reel_id') .'-'.  $frame->getData('frame_index'));
                //    } else {
                $this->log(__METHOD__ . __LINE__  . " SHANE queue regen " . $frame->getData('reel_id') .'-'. $frame->getData('frame_index'));
                $mediaPath =  BP . DS . 'media' . DS  ;
                foreach($this->_reset_frame_files_list as $fieldName) {

                    if($fieldName != 'text_file') {
                        $file =   $frame->getData($fieldName);

                        if(file_exists($mediaPath . $file)) {
                            @unlink($mediaPath . $file);
                        }
                        $frame->setData($fieldName, '');
                    }
                }

		$fieldList = array(
        		'left_file',
        		'right_file',
        		'rendered_file'
    		);

		$this->saveFrameFields($frame, $fieldList);

		// TODO we have some queue tha tnever have a file? wee should reload?
                mage::helper('chimage3d')->queueFrameForImageProcessing($frame->getReelId(), $frame->getEntityId(), $reel->getCustomerId(), false);
                $this->log("fired queueFrameForImageProcessing");
                //  }

            }

        } catch (Exception $e) {
            mage::logException($e);
            return $this->_returnResponse("Failed to save frame 007", false, $e->getMessage() );
        }
        return true;
    }

    private function log($x)
    {
        return Mage::log($x, null, $this->_log_file);
    }

    function _handleInboundImage($imageData, $frame)
    {

        //$this->log(__METHOD__ . __LINE__ . " wehave frame " . print_r($frame->getData(),1));

        $imageHelper = $this->getImageHelper();
        // $imageData = $this->_getRequestParam('image');
        $isSinglePixel = $imageData == self::ONE_PIXEL_PNG;
        $skipDecode = false;
        if(strpos($imageData, 'wysiwyg/RBStockArt') !== false || $isSinglePixel) {
            $skipDecode = true;
            if($isSinglePixel) {
                $imagePath = BP . DS . '..' . self::ONE_PIXEL_PNG;
            } else {
                $imagePath = BP . DS . 'media' . DS .  substr($imageData, strpos($imageData, Collinsharper_Image3d_Helper_Stockart::STOCK_ART_PATH));
            }

            if(file_exists($imagePath)) {
                $imageData = file_get_contents($imagePath);
            } else {
                $this->log(__METHOD__ . __LINE__ . " we had an issue with the stock art... " );
                $imageData = false;
            }
        } else {

            $imageData = $imageHelper->_processInboundBase64Image($imageData);
        }

        $fileName = $imageHelper->saveFrameImage($imageData, $frame, false, 'png', $skipDecode);

        $this->log(__METHOD__ . __LINE__ . " back from save $fileName " );
        return $fileName;
    }

    private function _save_image()
    {
        $frame = false;
        $imageHelper = $this->getImageHelper();

        if((int)$this->_getRequestParamReelId() > 0) {
            if(!$this->_checkValidReelCustomerPermissions($this->_getRequestParamReelId())) {
                return $this->_returnResponse("Invalid Reel Identifier 076");
            }
        }

//         we always use a frame to save an image Or we create one.
        if($this->_getRequestParamFrameId() > 0) {
            $frame = $this->_getFrameObject()->load($this->_getRequestParamFrameId());

            if(!$this->_checkValidFrame($frame)) {
                return $this->_returnResponse("Invalid Frame Identifier 075");
            }

        }

        $reel = $this->_load_create_reel();

        $frame = $this->_load_create_frame();


        try {

            $fileName = $this->_handleInboundImage($this->_getRequestParam('image'), $frame);

            $thumb = false;

            if($fileName) {
                $frame->setSourceFile($fileName);
                //$frame->setThumb('');
                $frame->setData('rendered_file','');
                $frame->setData('thumb_file','');
                $frame->setData('left_file','');
                $frame->setData('right_file','');
            } else {
                mage::logException(new Exception("failed to  update image "));
            }
//
//           if($frame && !$fileName) {
//               $thumb = $this->_updateFrameThumb($frame);
//           } else {
//               $thumb = $imageHelper->getThumb($fileName, Collinsharper_Image3d_Helper_Image::THUMB_WIDTH, Collinsharper_Image3d_Helper_Image::THUMB_HEIGHT, true);
//           }

            $this->log(__METHOD__ . __LINE__ . " we has some file inbound |$fileName| " .  $thumb );

            $this->log(__METHOD__ . __LINE__ . " we have frame " . print_r($frame->getData(),1));


            $fieldList = array(
                'source_file',
		'rendered_file',
		'thumb_file',
		'left_file',
		'right_file'
            );

            $this->saveFrameFields($frame, $fieldList);
	if($frame->getSourceFile() && $frame->getData('frame_data')) { // && $frame->getData('frame_index') == 0) {
		$this->log(__METHOD__ . __LINE__ . " force queue for center " );
            mage::helper('chimage3d')->queueFrameForImageProcessing($frame->getReelId(), $frame->getEntityId(), $reel->getCustomerId(), $frame);
	}

            $reelData = $this->_getAjaxReelData($reel);

            $reelData['posted_frame_index'] =  $frame->getFrameIndex();
            $reelData['posted_frame_entity_id'] =  $frame->getEntityId();
            $reelData['_action'] =  'save_image';

            $reelData['frame_data'] =  $reel->getAjaxFrames(true);

            return $this->_returnResponse("success", true, $reelData);

        } catch(Exception $e) {
            return $this->_returnResponse("Failed to save image  007 " . $e->getMessage());
        }
    }

}
