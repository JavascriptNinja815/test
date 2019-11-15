<?php

class Collinsharper_Image3d_Helper_Image extends Mage_Core_Helper_Data
{

    const USER_REEL_PATH = 'reel_builder/uploads/users';
    const ANON_REEL_PATH = 'reel_builder/uploads/anonymous';
    const COMPLETE_REEL_PATH = 'reel_builder/completed';
    // TODO ensure this strips out ../
    const IMAGE_NAME_REGEX = '/[^a-z0-9\-\_\.]+/i';
    const IMAGE_BASE64_PNG_PREFIX = 'data:image/png;base64,';
    const IMAGE_BASE64_JPEG_PREFIX = 'data:image/jpeg;base64,';
    const THUMB_WIDTH = 150; //121;
    const THUMB_HEIGHT = 150; //;
    const JPEG_QUALITY = 100;
    const JPEG_EXT = '.jpg';
    const PNG_EXT = '.png';
    const SEPIA_ADD = '_sepia';
    const FULL_ADD = '_full';
    const COLOR_ADD = '_color';
    const BNW_ADD = '_bnw';
    const FOUR_OH_FOUR_IMAGE = 'reel_builder_templates/404_image.png';
    //const MAX_UPLOAD_MUX = 1.25;
    //const MAX_UPLOAD_MUX = 1.1;
    const MAX_UPLOAD_MUX = 1;


    public function guid()
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }


    public function determineFileExtension($imageData)
    {
        $type = self::PNG_EXT;
        $strip = self::IMAGE_BASE64_PNG_PREFIX;
        if(strstr($imageData, self::IMAGE_BASE64_JPEG_PREFIX)) {
            $type = self::JPEG_EXT;
            $strip = self::IMAGE_BASE64_JPEG_PREFIX;
        }
        return $type;
    }

    public function _processInboundBase64Image($imageData)
    {
        // TODO we should only explode to 2
        $rawImage = explode(",", $imageData, 2);

        if(!$rawImage || !isset($rawImage[1])) {
            mage::throwException('Invalid Image data or invalid image type 088');
        }

        return $rawImage[1];
    }

    public function log($x, $force = false)
    {
        mage:: log($x, null, 'ch_reel_image.log', $force);
    }
    
    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function getCustomerId()
    {
        // tODO in the admin this wont work..
        return $this->getCustomerSession() && $this->getCustomerSession()->getCustomer() ? $this->getCustomerSession()->getCustomer()->getId() : false;
    }

    public function getMediaDir()
    {
        return Mage:: getBaseDir('media');
    }

    public function getThumb($image, $width = 140, $height = null, $force = false)
    {
        $baseFileName = basename($image);
        $fullImageName = $this->getMediaDir() . DS . $image;
        $thumbName =  md5($fullImageName)  . '_' .  $baseFileName;

        $thumbDir =  'cache' .  DS  . $thumbName[0] .  DS  . $thumbName[1] . DS ;
        $fullThumbDir = $this->getMediaDir() . DS . $thumbDir;

        $thumbFilename = $thumbDir . DS . $thumbName;

        if($force) {
            @unlink($thumbFilename);
        }


        if(!file_exists($fullImageName)) {
            $this->log(__METHOD__ . __LINE__ . " unable to locate image " . $fullImageName);
            return false;
        }

        if(file_exists($fullThumbDir . $thumbFilename)) {
            return $thumbFilename;
        }

        if(!is_dir($fullThumbDir)) {
            @mkdir($fullThumbDir, 0775, true);
        }

        if(!is_dir($fullThumbDir)) {
            $this->log(__METHOD__ . __LINE__ . " failed to find dir.. " . $fullThumbDir);
            return $image;
        }


        try {

          //  $this->log(__METHOD__ . " full path $fullImageName and  $fullThumbDir . $thumbName ");
            $imageObj = new Varien_Image($fullImageName);
            $imageObj->constrainOnly(TRUE);
            $imageObj->keepAspectRatio(TRUE);
            $imageObj->keepFrame(FALSE);
            $imageObj->resize($width, $height);
            $imageObj->save($fullThumbDir . $thumbName);

        } catch (Exception $e) {
            // could be an EPS a zip or anything..
            $this->log(__METHOD__ . __LINE__ . " failed resize " . $e->getMessage() );
        }

   //     $this->log(__METHOD__ . " checking for " . $fullThumbDir . $thumbName);

            return file_exists($fullThumbDir . $thumbName) ? $thumbFilename : $image;
    }

    public function getReelStoragePathPart()
    {
        return ($this->getCustomerId() ? self::USER_REEL_PATH :  self::ANON_REEL_PATH);
    }

    public function getReelStoragePath()
    {
        return $this->getMediaDir() . DS . $this->getReelStoragePathPart();
    }


    public function _updateFrameThumb($frame)
    {
        // TODO: create a table that generates thumbs in the background.

        $specificPath = 'thumb' . ($this->getCustomerId() ? DS . $this->getCustomerId() : '');

        $reelAssembler = Mage::getModel('chimage3d/reelassembly');

        //$image = $reelAssembler->_create_frame_image($image, $frame->getTextFile(), -1);
        $image = $reelAssembler->_create_frame_image($frame->getBackgroundFile(), $frame->getTextFile(), -1);

        $fullPath = $this->getReelStoragePath() . DS . $specificPath;

        if(!is_dir($fullPath)) {
            mkdir($fullPath, 0775, true);
        }

        if(!is_dir($fullPath)) {
            mage::throwException('cannot store files 040');
        }

        $actualWidth = Collinsharper_Image3d_Model_Reelassembly::FRAME_WIDTH;
        $actualHeight = Collinsharper_Image3d_Model_Reelassembly::FRAME_HEIGHT;
        $thumbWidth = $actualWidth *.1;
        $thumbHeight = $actualHeight *.1;

        $frame = imagecreatetruecolor($thumbWidth, $thumbHeight);

        imagealphablending($frame, false);
        imagesavealpha($frame, true);
        imagecopyresampled( $frame, $image,
            0, 0,
            0, 0,
            $thumbWidth, $thumbHeight,
            $actualWidth, $actualHeight );

        imagedestroy($image);

        $thumb = $this->_saveImageData($frame, $this->guid() . "_thumb", $fullPath, $specificPath);

        @imagedestroy($frame);

        return $thumb;
    }

    public function saveFrameImage($imageData, $frame = false, $imageName = false, $imageExt = false, $skipDecode = false)
    {
        if(!$imageExt) {
            $imageExt = $this->determineFileExtension($imageData);
        }

        $specificPath = $this->getCustomerId() ? DS . $this->getCustomerId() : '';

        if($frame && $frame->getReelId()) {
            $specificPath  .= DS . $frame->getReelId();
        }

//        if($frame && $frame->getEntityId()) {
//            $specificPath  .= DS . $frame->getEntityId();
//        }

        $fullPath = $this->getReelStoragePath() . DS . $specificPath;
        if(!is_dir($fullPath)) {
            mkdir($fullPath, 0775, true);
        }

        if(!is_dir($fullPath)) {
            mage::throwException('cannot store files 040');
        }

        if(!$imageName) {
            $imageName = $this->guid();
        }

        $originalImageName = $imageName;

        if(!$skipDecode) {
            $decodeData = base64_decode($imageData);
            if($decodeData !== false && $decodeData != '' ) {
                $imageData = $decodeData;
                unset($decodeData);
            }
        }

        $image = imagecreatefromstring($imageData);
	if(!$image) {
		mage::logException(new Exception(" issue with image " .	 substr($imageData, 0, 128)));
	// TODO we neeed to handle this 
	}
        imagealphablending($image, true);
        imagesavealpha($image, true);

        if(!$image) {
            mage::throwException('Invalid Image data or invalid image type 020');
        }


        $maxWidth = Collinsharper_Image3d_Model_Reelassembly::FRAME_WIDTH*Collinsharper_Image3d_Helper_Image::MAX_UPLOAD_MUX;
        $maxHeight = Collinsharper_Image3d_Model_Reelassembly::FRAME_HEIGHT*Collinsharper_Image3d_Helper_Image::MAX_UPLOAD_MUX;
        if($frame->getFrameIndex() == 0) {
            $this->log(__METHOD__ . " CENTER ");
            $maxWidth = Collinsharper_Image3d_Model_Reelassembly::CENTER_WIDTH*Collinsharper_Image3d_Helper_Image::MAX_UPLOAD_MUX;
            $maxHeight = Collinsharper_Image3d_Model_Reelassembly::CENTER_HEIGHT*Collinsharper_Image3d_Helper_Image::MAX_UPLOAD_MUX;
        }
        $actualWidth = imagesx($image);
        $actualHeight = imagesy($image);

        if($actualWidth > $maxWidth || $actualHeight > $maxHeight) {

            if($actualWidth >= $actualHeight) {
                $this->log(__METHOD__ . " wider or square ");
                $destinationWidth = $maxWidth;
                $destinationHeight = $actualHeight * ($maxWidth/$actualWidth);
            } else {
                $this->log(__METHOD__ . " taller?");
                $destinationWidth = $actualWidth * ($maxHeight/$actualHeight);
                $destinationHeight = $maxHeight;
            }

            $this->log(__METHOD__ . " MAX of $maxWidth, $maxHeight ");
            $this->log(__METHOD__ . " resizing to $actualWidth, $actualHeight ");
            $this->log(__METHOD__ . " resizing to $destinationWidth, $destinationHeight ");
                $tempPic = ImageCreateTrueColor($destinationWidth, $destinationHeight);
                imagecopyresampled( $tempPic, $image, 0, 0, 0, 0, $destinationWidth, $destinationHeight, $actualWidth, $actualHeight );
                imagedestroy( $image );
                $image = $tempPic;
        }


        return $this->_saveImageData($image, $originalImageName, $fullPath, $specificPath);
    }

    function _saveImageData($image, $originalImageName, $fullPath, $specificPath)
    {
        $returnImageName = $imageName = $this->_generateImageName($originalImageName, self::FULL_ADD, self::JPEG_EXT);

        //    if(!imagejpeg($image, $fullPath . DS . $imageName, self::JPEG_QUALITY )) {

//        if(!imagepng($image, $fullPath . DS . $imageName)) {
        if(!imagejpeg($image, $fullPath . DS . $imageName, self::JPEG_QUALITY)) {
            mage::throwException('cannot store files 041');
        }

        // TODO test using a lower res jpeg for the reel then swithcing to png later
//       $imageName = $this->_generateImageName($originalImageName, self::COLOR_ADD, self::JPEG_EXT);
//
//        if(!imagejpeg($image, $fullPath . DS . $imageName, self::JPEG_QUALITY )) {
//            mage::throwException('cannot store files color 041');
//        }

        imagedestroy($image);

//        mage::log(__METHOD__ . __LINE__ . " we have data " . $this->getReelStoragePathPart() . DS . $specificPath . DS . $returnImageName);

        return $this->getReelStoragePathPart() . DS . $specificPath . DS . $returnImageName;
    }

    function _generateImageName($originalImageName, $additional = '', $extension = false)
    {
        if(!$extension) {
            $extension = self::PNG_EXT;
        }
        // sanity check the file names..
        return str_replace('__', '_', preg_replace(self::IMAGE_NAME_REGEX, '_', str_replace(self::PNG_EXT, '', $originalImageName))) . $additional . $extension;
    }
}
