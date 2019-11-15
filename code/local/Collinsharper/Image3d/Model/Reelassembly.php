<?php


// //extends Collinsharper_Image3d_Model_Abstract

// dont think we need the class extension
if(!class_exists('Varien_Profiler')) {
    require_once(dirname(__FILE__ ) . '/../../../../../../lib/Varien/Profiler.php');
    require_once(dirname(__FILE__ ) . '/../Helper//Generation.php');
}

class Collinsharper_Image3d_Model_Reelassembly
{

    private $reelConfiguration = array();
    private $starttime = false;
    private $_frame_data = false;
    const LOG_FILE = 'ch_reel_builder.log';
    const DEBUG_FILE = 'ch_reel_debugger.log';

    const FRAME_GENERATION_BLEED = 6;
    const FRAME_WIDTH = 1126;
    const FRAME_HEIGHT = 1018;
    const THUMB_WIDTH = 85; //121;
    const THUMB_HEIGHT = 90; //;

    const FRAME_BLEED = 40;
    const TEXT_DRAG_BLEED_MOD = 1.15;
    // bleed for center art
    const CENTER_WIDTH = 4590; //4580;
    const CENTER_HEIGHT = 4590; //4580;
    const CANVAS_DIM_MODIFIER = 2;
    const CENTER_CANVAS_DIM_MODIFIER = 10;
    const CANVAS_MASK = '/media/reel_builder_templates/overlay.png';
    const CENTER_CANVAS_MASK = '/media/reel_builder_templates/center-canvas-overlay.png';
    const DEFAULT_TEXT = "Made on image3d.com";

    var $_frame_placement = array();

    CONST DEBUG_WRITE_ALL_IMAGES = false;
    const DEBUG_PREFIX = "DEBUG_IMG_REEL_";

    function __construct()
    {
        $this->_construct();
    }

    function getMediaPath()
    {
        if(!class_exists('Mage')) {
            $mediaPath = dirname(__FILE__ ) . '/../../../../../../media';
            define('DS', '/');
        } else {
            $mediaPath = Mage::getBaseDir('media');
        }
        return $mediaPath;
    }

    protected function _construct()
    {

        Varien_Profiler::start(__METHOD__ );

        if(!defined('BP')) {
            $bp = dirname(__FILE__) . '/../../../../../../';
            define('BP', $bp);
        }

        ini_set('memory_limit','1524M');

        $helper = new Collinsharper_Image3d_Helper_Generation;

        // TODO put in magento cache - use from there or regen once and  save globvally
        $this->_frame_placement = $helper->getPositions();

        $mediaPath = $this->getMediaPath();
        $this->reelConfiguration = array(
            //media/reel_builder_templates/master_template.png',
            'center_ring_cover' => $mediaPath . DS .  'reel_builder_templates/master_template_frame_ring.png',
            'master_template' => $mediaPath . DS .  'reel_builder_templates' . DS . 'master_template.png',
            'BARCODE' => array(
                'barcode_font' => $mediaPath . DS .  'reel_builder_templates' . DS . 'free3of9.ttf',
                'x' => 1056+3,
                'y' => 1056,
            ),

            // the copywrite will be run in 1 - 3 lines split for the QR code
            'copywrite' => array (
                '© Image3D Milwaukie, OR 97222 USA',
                'United Stated Patent Number 6,295,067'
            ),

            // test 51.4 or 360 / 7
            'default_frame_rotate' => 360 / 7,


            // this is the window that the leeft and rigth sit in
            //   'frame_windows_width' => 3680,
            'frame_windows_width' => self::FRAME_WIDTH * 2, // - 84, // wtf are we minus 84 :?
            // TODO reel assembly is broke now since we did this..
            'frame_windows_width' => 3690 * 2 - 84,

            // exact pixels between frames plus 5034 +  //7300
                'frame_windows_width' => 5034 + ( self::FRAME_WIDTH * 2), // 7286,

            'frame_width' => self::FRAME_WIDTH, //612
            'frame_height' => self::FRAME_HEIGHT, // 557

            // phil said 10 total
//            'text_pixel_shift' => 15,
            'text_pixel_shift' => 5,
            'image_pixel_shift' => 40,

            'center_art' => array(
                'rotate' => 0,
                'mask' => $mediaPath . DS .  'reel_builder_templates' . DS . 'C3DBlackMaskTemplate-whitecircle.png',

                // 2072.5
            // 2072.5

                'x' => 2070,
                'y' => 2070,
//
//                'x' => 2080,// 1056+3,
//                'y' => 2077,// 1056,
                'act_width' => self::CENTER_WIDTH,
                'act_height' => self::CENTER_HEIGHT,

            ),

            'center_dot' => array(
                'path' => $mediaPath . DS .  'reel_builder_templates' . DS . 'center-dot.png',
                'x' => 2080 + (4572/2), // 1056+3+1155,
                'y' => 2080 +  (4572/2), //1056+1155,
                'w' => 144*2,
                'h' => 144*2,
            ),

            'QR' => array(
                'rotate' => 45,
                'x' => 4142+35-80, //(1761+260+50)*2,
                'y' => 7888+180, //(3489+455)*2,
            ),

            'frames' => array(
                '1' => array(
                    'x' => 725,
                    'y' => 3858.5,
                    'rotate' => 0,
                ),

                '2' => array(
                    'x' => 1698.486,
                    'y' => 6012.932,
                    'rotate' => 231.43 + 180,
                ),

                '3' => array(

                    'x' => 4429.900,
                    'y' => 6701.793,
                    'rotate' => 282.86 + 180,
                ),

                '4' => array(
                    'x' => 6408.535,
                    'y' => 4998.171,
                    'rotate' => 334.29  + 180,
                ),

                '5' => array(
                    'x' => 6408.535,
                    'y' => 2328.485,
                    'rotate' => 205.71 ,
                ),

                '6' => array(
                    'x' => 4429.900,
                    'y' => 703.0619,
                    'rotate' => 77.14  +180 ,
                ),

                '7' => array(
                    'x' => 1698.486,
                    'y' => 1202.322,
                    'rotate' => 128.57 +180,
                ),

                '8' => array(
                    'x' => 6878,
                    'y' => 3858.5,
                    'rotate' => 0,
                ),

                '9' => array(
                    'x' => 5534.818,
                    'y' => 1202.322,
                    'rotate' => 51.43 ,
                ),

                '10' => array(
                    'x' => 3060.729,
                    'y' => 703.0619,
                    'rotate' => 102.86,
                ),

                '11' => array(

                    'x' => 864.8740,
                    'y' => 2328.485,
                    'rotate' => 154.29,
                ),

                '12' => array(
                    'x' => 864.8740,
                    'y' => 4998.171,
                    'rotate' => 25.71 + 180,
                ),

                '13' => array(
                    'x' => 3060.729,
                    'y' => 6701.793,
                    'rotate' => 257.14,
                ),

                '14' => array(
                    'x' => 5534.818,
                    'y' => 6012.931,
                    'rotate' => 308.57,
                ),
            ),
        );

        Varien_Profiler::stop(__METHOD__);
    }

    function iniGetBytes($val)
    {
        $val = trim(ini_get($val));
        if ($val != '') {
            $last = strtolower(
                $val{strlen($val) - 1}
            );
        } else {
            $last = '';
        }
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    public function getConstant($x)
    {
        if($x == 'CANVAS_TEXT_DRAG_BLEED') {
            return (self::FRAME_BLEED*self::TEXT_DRAG_BLEED_MOD)+(self::FRAME_BLEED/2);
        }

        if($x == 'CANVAS_WIDTH' || $x == 'CANVAS_HEIGHT') {
            if($x == 'CANVAS_WIDTH') {
                $x = self::FRAME_WIDTH/2;
            } else {
                $x = self::FRAME_HEIGHT/2;
            }
            return $x + self::FRAME_BLEED/2 + self::FRAME_GENERATION_BLEED/2;
        }

        if($x == 'CENTER_CANVAS_WIDTH' || $x == 'CENTER_CANVAS_HEIGHT') {
            if($x == 'CENTER_CANVAS_WIDTH') {
                $x = self::CENTER_WIDTH/self::CENTER_CANVAS_DIM_MODIFIER;
            } else {
                $x = self::CENTER_HEIGHT/self::CENTER_CANVAS_DIM_MODIFIER;
            }
            return $x;
        }

        return self::$x;
    }

    public function log($x, $force = false)
    {

        $force = true ; // self::DEBUG_WRITE_ALL_IMAGES || $force ? true: false;
        if($force) {
            if(0 && class_exists('Mage')) {
                mage::log($x , null, self::LOG_FILE, $force);
            } else {
                file_put_contents(BP . '/var/log/' . self::LOG_FILE, date('c') . ' - ' . $x . "\n", FILE_APPEND);
            }
        }
    }

    public function logProcess($x, $force = false)
    {

        $force = true ; // self::DEBUG_WRITE_ALL_IMAGES || $force ? true: false;
        if($force) {
            if(0 && class_exists('Mage')) {
                mage::log($x , null, self::DEBUG_FILE, $force);
            } else {
                file_put_contents(BP . '/var/log/' . self::DEBUG_FILE, date('c') . ' - ' . $x, FILE_APPEND);
            }
        }
    }

    public function setStarttime($x)
    {
        $this->starttime = $x;
    }

    public function generateAnaglyphPreview($left = false, $right = false, $image = false, $text = false)
    {
        if(!class_exists('Mage')) {
            throw new Exception("Requires magento");
        }

        $instance = Mage::getModel('chimage3d/anaglyph');

        if(!$left && !$right && !$image) {
            $this->log(__METHOD__ . " failed to generate anaglyph ");
            die(__METHOD__ . "required...");
        }

        if(is_string($left) && file_exists($left)) {
            $left = $this->_create_from_png($left);
        }

        if(is_string($right) && file_exists($right)) {
            $right = $this->_create_from_png($right);
        }


        if($image) {

            throw new exception("this is no longer functional as the code never placed the text on the image properly");
            $instance->leftImgContents = $this->_create_frame_image($image, $text, 1);
            $instance->rightImgContents = $this->_create_frame_image($image, $text, 0);
        } else if($left && $right) {
            $instance->setImages($left, $right);
            $instance->loadImages();
        }


//it takes about 13 seconds for a 1 MP JPEG image to be processed and about 33 seconds for a 2.5 MP PNG image to be processed
        $stereoIMG = & $instance->createStereoImage();
        if($stereoIMG) {
            //header("Content-type: image/png");

            imagepng($stereoIMG, Mage::getBaseDir() . DS . 'var' . DS . "stereo.png");
            return $stereoIMG;

        } else {
            foreach($instance->errors as $error) {
                $this->log(__METHOD__ . " Errors: " . $error );
            }
        }

    }


    public function buildReelByReelId($reelId)
    {
        if(!class_exists('Mage')) {
            throw new Exception("Requires magento");
        }

	//See if there is still a frame generation process running for this reel
	$strPs = 'ps aux | grep -v grep | grep ' . '"'.$reelId.'"';
	ob_start();
	passthru($strPs);
	$output = trim(ob_get_contents());
	ob_end_clean();
                    
	if(strpos($output, 'generateFrameImages') !== false) {
		$this->logProcess("Reel id: {$reelId} {$output}");
                sleep(7);
	}

        Varien_Profiler::start(__METHOD__);
        $reel = Mage::getModel('chreels/reels')->load($reelId);

        $frameImage = array();
        $frameImageText = array();

        $imagePrefix =  BP . DS . 'media' . DS ;

        $this->_frameData = $reel->getAjaxFrames();
        foreach($this->_frameData as $idx => $frame) {
            if($idx == 0) {
                $centerArt = $imagePrefix . $frame['rendered_file'];
                continue;
            } else {
                $frameImage[] = $imagePrefix . $frame['background_file'];
                $frameImageText[] = $imagePrefix . $frame['text_file'];
            }
        }
        Varien_Profiler::stop(__METHOD__);
        return $this->buildPrintableReel($reelId, $frameImage, $frameImageText, $centerArt);
    }

    function debugWriteAllImages ($img, $name, $force = false)
    {
		return ;
        if(self::DEBUG_WRITE_ALL_IMAGES || $force) {

            $dir = BP . DS . 'var/reel_builder_debug/';

            if (!is_dir($dir)) {
                mkdir($dir);
            }

            //imagepng($img, BP . DS . self::DEBUG_PREFIX . '_' . $name);

            $filename = implode('_',array(
                $dir . self::DEBUG_PREFIX,
                microtime(true),
                $name . '.jpg',
            ));

            imagejpeg($img, $filename, 85);
        }

    }

    public function buildPrintableReel($reelId, $frameImage, $frameImageText, $centerArt)
    {
        @unlink(BP . DS . 'var' . DS . 'log' . DS . self::LOG_FILE);

        $reel = $this->_setup_base_reel();
        imagealphablending($reel, true);
        imagesavealpha($reel, true);

        $this->debugWriteAllImages($reel, 'base_reel.png');

        $this->debugWriteAllImages($reel, 'center.png');


        $this->log(__METHOD__ . __LINE__ . " SHANE CENTER " . $centerArt);
        $reel = $this->_add_center_art($reel, $centerArt);
        $this->log(__METHOD__ . __LINE__ . " SHANE CENTER " . $centerArt);

        $this->debugWriteAllImages($reel, 'with_center.png', true);

        if(!$reelId || !$frameImage || !$frameImageText) {
            throw new Exception("Missing required arguments.");
        }

        if(count($frameImage) > 7 || count($frameImageText) > 7) {
            throw new Exception("Stereo generation not yet supported");
        }

        foreach($frameImage as $fIdx => $fImage) {

//            $viewPort = $this->_generate_frame_viewport($fIdx+1, $fImage, $frameImageText[$fIdx]);
//            $reel = $this->_place_frame_viewport($reel, $viewPort, $fIdx+1);
            $detailsLeft = $this->reelConfiguration['frames'][$fIdx+1];
            $detailsRight = $this->reelConfiguration['frames'][$fIdx+1 + 7];


            $frames = array();
            if($this->_frame_data && isset($this->_frame_data[$fIdx+1]) &&
                isset($this->_frame_data[$fIdx+1]['left_file']) &&
                isset($this->_frame_data[$fIdx+1]['right_file']) &&
                $this->_frame_data[$fIdx+1]['left_file']) {

                $this->log(__METHOD__ . " using cached FRAMES!!!  " . ($fIdx+1));
                $frames[0] = $this->_frame_data[$fIdx+1]['left_file'];
                $frames[1] = $this->_frame_data[$fIdx+1]['right_file'];
            } else {
                $frames = $this->_generate_frame_left_right($fIdx+1, $fImage, $frameImageText[$fIdx]);
            }

            $frameWidth = imagesx($frames[0]);


            $frameHeight = imagesy($frames[0]);

            imagealphablending($reel, true);
            imagesavealpha($reel, true);
            // frame 0 is the left frame
            // frame 1 is the right frame
            $detailsLeft = $this->_frame_placement[$fIdx + 1]['cords'];
            $detailsRight = $this->_frame_placement[$fIdx + 1 + 7]['cords'];

            imagecopy($reel, $frames[0], $detailsLeft['x'], $detailsLeft['y'], 0, 0, $frameWidth, $frameHeight);

            imagecopy($reel, $frames[1], $detailsRight['x'], $detailsRight['y'], 0, 0, $frameWidth, $frameHeight);


            $this->log(__METHOD__ . __LINE__ . " SHANE we have {$fIdx} plus 1  {$detailsLeft['x']}, {$detailsLeft['y']}  , $frameWidth, $frameHeight ");

            $this->debugWriteAllImages($reel, $fIdx+1 . '_framedAdded.png', false);

        }

        $reel = $this->_add_base_mask($reel);

        $reel = $this->_add_qr_image($reel, $reelId);
        // rotate 90 counter

        return $reel;
    }
//    public function generateFinalThumbRotateReel($finalImage, $fullLocalPathFile, $localFileSystemPath, $path, $fullLocalPathFile, $fullLocalPathThumb)
    public function generateFinalThumbRotateReel($finalImage, $fullLocalPathFile, $localFileSystemPath, $path, $fullLocalPathThumb)
    {
        if($finalImage) {
            if(!is_dir($localFileSystemPath . $path) && !mkdir($localFileSystemPath . $path, 0775, true)) {
                Mage::logException(new Exception("issue creating directory"));
                return $this->_returnResponse("Failed to generate reel 077");
            }
        }


        $imageScale = .25;
        $srcWidth = imagesx($finalImage);
        $srcHeight = imagesy($finalImage);
        $newWidth = imagesx($finalImage)*$imageScale;
        $newHeight = imagesy($finalImage)*$imageScale;


        $thumbResource = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumbResource, $finalImage, 0, 0, 0, 0, $newWidth, $newHeight, $srcWidth, $srcHeight);
  //      imagedestroy($finalImage);
        imagejpeg($thumbResource, $fullLocalPathThumb, 75);
        imagedestroy($thumbResource);


        $blackBg = imagecolorallocate($finalImage, 0, 0, 0);
        $finalImage = imagerotate ($finalImage, 90, $blackBg);
        imagejpeg($finalImage, $fullLocalPathFile, 100);

        return $fullLocalPathFile;
    }


    public function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        Varien_Profiler::start(__METHOD__);

        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);

        // copying relevant section from background to the cut resource
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);

        // copying relevant section from watermark to the cut resource
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);

        // insert cut resource to destination image
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
        Varien_Profiler::stop(__METHOD__);

    }

    public function convertSysMem($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    function _setup_base_reel()
    {
        Varien_Profiler::start(__METHOD__);
        //$background = $this->_create_from_png($this->reelConfiguration['master_template']);
        $identify = getimagesize($this->reelConfiguration['master_template']);
        $background = imagecreatetruecolor($identify[0], $identify[1]);
        imagesavealpha($background, true);

        //$colour = imagecolorallocatealpha($background, 0, 0, 0, 127);
        $colour = imagecolorallocate($background, 0, 0, 0);
        imagefill($background, 0, 0, $colour);

        Varien_Profiler::stop(__METHOD__);
        return $background;
    }

    function _add_base_mask($background)
    {
        Varien_Profiler::start(__METHOD__);

        $mask = $this->_create_from_png($this->reelConfiguration['master_template']);

        $this->debugWriteAllImages($mask, 'mask.png');

        $cWidth = imagesx($mask);
        $cHeight = imagesy($mask);

        imagecopy($background, $mask, 0, 0, 0, 0, $cWidth, $cHeight);

        imagedestroy($mask);

        Varien_Profiler::stop(__METHOD__);

        return $background;
    }

    function _add_side_barcode($background, $reelId)
    {
        // TODO this doesnt work - barcode was too small
        throw new Exception("fix your monkey code");
        Varien_Profiler::start(__METHOD__);

        $reelISBIgMultiplier = 100;
        $height = 40 * $reelISBIgMultiplier;
        $newWidth = ((strlen($reelId)*20)+41)*$reelISBIgMultiplier; // allocate width of barcode. each character is 20px across, plus add in the asterisk's
        $font_height = 60 * 10; // barcode font size. anything smaller and it will appear jumbled and will not be able to be read by scanners

        $angle = 45;
        $barcode = imagecreate($newWidth, $height); // open the blank image

        imagealphablending($barcode, true); // set alpha blending on
        imagesavealpha($barcode, true); // save alphablending setting (important)

        $white = imagecolorallocate($barcode, 255, 255, 255);
        $black = imagecolorallocate($barcode, 0, 0, 0); // colour of barcode

        imagecolortransparent($barcode, $white);

        imagettftext($barcode, $font_height, $angle, 1, $height, $black,  $this->reelConfiguration['BARCODE']['barcode_font'], "*{$reelId}*"); // add text to image


        imagecopy($background, $barcode, $this->reelConfiguration['BARCODE']['x'], $this->reelConfiguration['BARCODE']['y'], 0, 0, $newWidth, $height/3);
        imagedestroy($barcode);

        Varien_Profiler::stop(__METHOD__);
        return $background;
    }

    function _add_qr_image($background, $reelId)
    {
        Varien_Profiler::start(__METHOD__);
        $reelId =  $reelId;

        $code_params = array('text' => $reelId,
            'backgroundColor' => '#FFFFFF',
            'foreColor' => '#000000',
            'padding' => 1,  //array(10,5,10,5),
            'moduleSize' => 19
            //'moduleSize' => 21
            //'moduleSize' => 23
            //'moduleSize' => 8
        );

        $renderer_params = array('imageType' => 'png', 'sendResult' => false);

        $qrCode = Zend_Matrixcode::render('qrcode', $code_params, 'image', $renderer_params);

        $qrBgColor = imageColorAllocateAlpha($qrCode, 0, 0, 0, 127);
        $qrCode = imagerotate($qrCode, $this->reelConfiguration['QR']['rotate'], $qrBgColor);
        $qrWidth = imagesx($qrCode);
        $qrHeight = imagesy($qrCode);

        imagecopy($background, $qrCode, $this->reelConfiguration['QR']['x'], $this->reelConfiguration['QR']['y'], 0, 0, $qrWidth, $qrHeight);
        imagedestroy($qrCode);

        Varien_Profiler::stop(__METHOD__);

        return $background;

    }

    function _create_from_png($file, $isJpeg = false)
    {
        // TODO enforce some base path here?
        $this->log(__METHOD__ . __LINE__ . " we have " . $file);
        if(!$file || !file_exists($file)) {
            $this->log("missing file $file 001 ", true);
        }

        if(!$isJpeg && strstr($file, 'png')) {
            $image = imagecreatefrompng($file);
        } else {
            $image = imagecreatefromstring(file_get_contents($file));
        }

        if(!$image) {
            $this->log("missing file $file 002 ", true);
        }
        return $image;
    }

    function _add_center_art($background, $centerArt)
    {

        Varien_Profiler::start(__METHOD__);

//            $centerImage = $this->_create_from_png($centerArt, true);
        $centerImage = imagecreatefromjpeg($centerArt);
        imagealphablending($centerImage, false);
        imagesavealpha($centerImage, true);

        $reelWidth = imagesx($background);
        $reelHeight = imagesy($background);
        $cWidth = imagesx($centerImage);
        $cHeight = imagesy($centerImage);
        // Due to the reel not being math perfect - we cant use these
        $placementX = ($reelWidth/2) - ($cWidth/2);
        $placementY = ($reelHeight/2) - ($cHeight/2);
        $placementX = $this->reelConfiguration['center_art']['x'];
        $placementY = $this->reelConfiguration['center_art']['y'];


        imagecopy($background, $centerImage, $placementX, $placementY, 0, 0, $cWidth, $cHeight);

        imagedestroy($centerImage);

        // imagealphablending($centerImage, false);
        imagesavealpha($background, true);

        Varien_Profiler::stop(__METHOD__);

        Varien_Profiler::start(__METHOD__ . " :: CENTER RING ");
        //center_ring_cover

        $centerImage = $this->_create_from_png($this->reelConfiguration['center_ring_cover']);
        imagealphablending($centerImage, false);
        imagesavealpha($centerImage, true);

        $cWidth = imagesx($centerImage);
        $cHeight = imagesy($centerImage);

        imagecopy($background, $centerImage, 0, 0, 0, 0, $cWidth, $cHeight);

        imagedestroy($centerImage);

        // imagealphablending($centerImage, false);
        imagesavealpha($background, true);

        Varien_Profiler::stop(__METHOD__ . " :: CENTER RING ");

        return $background;
    }

    function dead_add_center_art($background, $centerArt)
    {
        Varien_Profiler::start(__METHOD__);

        $centerImage = $this->_create_from_png($centerArt, true);

        $this->log(__METHOD__ . __LINE__ . " SHANE we have " . $centerArt . " and " . $centerImage);

        $this->debugWriteAllImages($centerImage, 'OIcenter.png', true);
        $cWidth = imagesx($centerImage);
        $cHeight = imagesy($centerImage);

        imagealphablending($centerImage, false);
        imagesavealpha($centerImage, true);
        imagealphablending($background, false);
        imagesavealpha($background, true);

        $this->log(__METHOD__ . __LINE__ . " SHANE we have " . $centerArt);
        imagecopy($background, $centerImage,
            $this->reelConfiguration['center_art']['x'],
            $this->reelConfiguration['center_art']['y'],
            0, 0, $cWidth, $cHeight);

        imagedestroy($centerImage);
        $centerImage = false;



        Varien_Profiler::stop(__METHOD__);

        Varien_Profiler::start(__METHOD__ . " :: CENTER RING ");


        $this->debugWriteAllImages($background, 'BPwithcenter.png', true);

//        $centerImage = $this->_create_from_png($this->reelConfiguration['center_ring_cover'], false);
        $ringImage = imagecreatefrompng($this->reelConfiguration['center_ring_cover']);
        $cWidth = imagesx($ringImage);
        $cHeight = imagesy($ringImage);
        $bWidth = imagesx($ringImage);
        $bHeight = imagesy($ringImage);
        $this->log(__METHOD__ . __LINE__ . " ring W $cWidth H $cHeight B $bWidth H $bHeight ");

        imagepng($ringImage, BP . DS . 'b-shane-justring.png');
        imagejpeg($ringImage, BP . DS . 'b-shane-justring.jpeg');


        imagealphablending($ringImage, true);
       // imagesavealpha($ringImage, true);

        $this->debugWriteAllImages($ringImage, 'justring.png', true);

        imagepng($ringImage, BP . DS . 'shane-justring.png');
        imagejpeg($ringImage, BP . DS . 'shane-justring.jpeg');

        imagealphablending($background, false);
        imagesavealpha($background, true);

        imagecopy($background, $ringImage, 0, 0, 0, 0, $cWidth, $cHeight);

        imagealphablending($background, false);
        imagesavealpha($background, true);


        $this->debugWriteAllImages($background, 'bgcenterring2.png', true);



        imagedestroy($centerImage);
//
//        imagealphablending($background, false);
//        imagesavealpha($background, true);
        $this->debugWriteAllImages($background, 'bgcenterring3.png', true);



        Varien_Profiler::stop(__METHOD__ . " :: CENTER RING ");


        Varien_Profiler::start(__METHOD__ . " :: DOT ");

//        $centerImage = $this->_create_from_png($this->reelConfiguration['center_dot']['path']);
//        imagealphablending($centerImage, false);
//        imagesavealpha($centerImage, true);
//
//        $cWidth = imagesx($centerImage);
//        $cHeight = imagesy($centerImage);
//
//        imagecopy($background, $centerImage, $this->reelConfiguration['center_dot']['x'], $this->reelConfiguration['center_dot']['y'], 0, 0, $cWidth, $cHeight);
//
//        imagedestroy($centerImage);
//
//        imagesavealpha($background, true);

        // we do not draw the white dot, but if we wanted to..
//        $white = imagecolorallocate($background, 255, 255, 255);
//
//        $configData = $this->reelConfiguration['center_dot'];
//
//        imagefilledarc ( $background , $configData['x'] , $configData['y'], $configData['w'] , $configData['h'], 0 , 360 , $white , IMG_ARC_PIE );

        Varien_Profiler::stop(__METHOD__ . " :: DOT ");

        return $background;
    }

    function _create_frame_image($image, $text = false, $shift = 0)
    {
        Varien_Profiler::start(__METHOD__);

        if(!file_exists($image)) {
            if(file_exists(Mage::getBaseDir('media') . DS . $image)) {
                $image = Mage::getBaseDir('media') . DS . $image;
            } else {
                mage::throwException("Cannot locate file " . $image);
            }
        }

        $frameImage = $this->_create_from_png($image);

        if($text) {
            if(!file_exists($text)) {
                if(file_exists(Mage::getBaseDir('media') . DS . $text)) {
                    $text = Mage::getBaseDir('media') . DS . $text;
                } else {
                    mage::throwException("Cannot locate file " . $text);
                }
            }
            $frameText = $this->_create_from_png($text);
        }

        $fWidth = imagesx($frameImage);
        $fHeight = imagesy($frameImage);
        // left is 1 right is 0
      //  $srcX = $shift == 1 ? self::FRAME_BLEED/2 : 0;
        $srcX = 0;
        // we shift them image on frame placement.
        //$frame = imagecreatetruecolor($this->reelConfiguration['frame_width'], $this->reelConfiguration['frame_height']);
        $frame = imagecreatetruecolor($fWidth-$srcX, $fHeight);


        $this->log(__METHOD__ .  __LINE__ .  "testing frame shift " . $srcX);


        imagealphablending($frame, true);
        imagesavealpha($frame, true);

        imagecopymerge($frame, $frameImage, 0, 0, $srcX, 0, $fWidth-$srcX, $fHeight, 100);

        imagedestroy($frameImage);

        imagealphablending($frame, true);
        imagesavealpha($frame, true);

        // left is 1 right is 0 we shift text 5 each way
        $srcX = $shift == 1 ? 0 : 5;
        $dstX =  $shift == 1 ? 0 : 5;
       // why are we chanigng where its placed?

        $dstX =  $shift == 1 ? 25 : 0;
        //     $dstX = ($this->reelConfiguration['text_pixel_shift']/2) * ($shift == 0 ? 1 : -1);
        $this->log(__METHOD__ .  __LINE__ .  "testing text shift " . $srcX);

        if($text) {

            $fWidth = imagesx($frameText);
            $fHeight = imagesy($frameText);
            //$this->imagecopymerge_alpha($frame, $frameText, 0, 0, $srcX, 20, $frameWidth-$srcX, $frameHeight, 100);
            $this->imagecopymerge_alpha($frame, $frameText, $dstX, 0, $srcX, 0, $fWidth-$srcX, $fHeight, 100);
        }

        Varien_Profiler::stop(__METHOD__);
        return $frame;
    }

    function _create_frame_window($frameImages, $frameNumber, $background)
    {
        Varien_Profiler::start(__METHOD__ . ":: $frameNumber");

        $viewportWidth = $this->reelConfiguration['frame_windows_width'];
        $viewportHeight = $this->reelConfiguration['frame_height'];
        // create a frame view port window
        $window = imagecreatetruecolor($viewportWidth, $viewportHeight);
        imagesavealpha($window, true);

        $trans_colour = imagecolorallocatealpha($window, 255, 255, 255, 127);
        imagefill($window, 0, 0, $trans_colour);
        imagealphablending($window, true);
        imagesavealpha($window, true);

        imagealphablending($frameImages['L'], true);
        imagesavealpha($frameImages['L'], true);

        $fWidth = imagesx($frameImages['L']);
        $fHeight = imagesy($frameImages['L']);

        // copy left into frame view port window

        imagecopy($window, $frameImages['L'], 0, 0, 20, 0, $fWidth-20, $fHeight);
        imagealphablending($window, true);
        imagesavealpha($window, true);

        imagealphablending($frameImages['R'], true);
        imagesavealpha($frameImages['R'], true);


        // TODO: redundant?
        $fWidth = imagesx($frameImages['R']);
        $fHeight = imagesy($frameImages['R']);

        // copy right into frame view port window

        imagecopy($window, $frameImages['R'],$viewportWidth-$fWidth - (self::FRAME_BLEED/2), 0, 0, 0, $fWidth, $fHeight);
        imagealphablending($window, true);
        imagesavealpha($window, true);



        $rotate = $this->_getFrameRotate($frameNumber);

        if($rotate > 0) {

            Varien_Profiler::start(__METHOD__ . "::Rotate $frameNumber");

            $rWindow = imagerotate($window, 360-$rotate, $trans_colour,0);
            imagealphablending($rWindow, true);
            imagesavealpha($rWindow, true);
            Varien_Profiler::stop(__METHOD__ . "::Rotate $frameNumber");
            $window = $rWindow;
        }
        $fWidth = imagesx($window);
        $fHeight = imagesy($window);


        Varien_Profiler::stop(__METHOD__ . ":: $frameNumber");

        return $window;
    }

    function _getFrameRotate($frameNumber)
    {
        // tODO we need verify this?
     //   return  (360/7) * (($frameNumber - 1));
        $frameRotateFix = array(
            1 => 0,
            2  => 7,
            3 => 6,
            4 => 5,
            5 => 4,
            6 => 3,
            7 => 2
        );
        $rotateRatio = $this->reelConfiguration['default_frame_rotate'];

        return (($rotateRatio * $frameRotateFix[$frameNumber]) - $rotateRatio);

    }

    function _generate_frame_left_right($frameNumber, $image, $text)
    {
        Varien_Profiler::start(__METHOD__ . " ::  Frame Number: $frameNumber $image, $text ");

        if(!$image || !is_file($image)) {
            // set it ass the 1.x pix
            $this->log("potentially missing frame TEXT image Frame Number: $frameNumber $image, $text", true);
            //Mage::logException(new Exception("potentially missing frame BG  image Frame Number: $frameNumber $image"));
            $image = BP . DS . DS . 'media/reel_builder_templates/1x1.png';
        }
       
        $frameImage = $this->_create_from_png($image);
        // TODO  this  could be an isssue where phantom has not completed yet.
        // check the canvas fort text object/ if its there - regen and wait for phantom?
        if(!$text || !is_file($text)) {
            // set it ass the 1.x pix

            $this->log("potentially missing frame TEXT image Frame Number: $frameNumber $image, $text", true);

            $text = BP . DS . DS . 'media/reel_builder_templates/1x1.png';
        }

        $frameText = $this->_create_from_png($text);

        $srcFrameWidth = $frameWidth = imagesx($frameImage);
        $srcFrameHeight = $frameHeight = imagesy($frameImage);

        $frameCreateBleed = self::FRAME_GENERATION_BLEED/2;
        $frameCreateBleed = 0;
        $topCropRemoveBleed = -1 * ((self::FRAME_BLEED   / 2) - $frameCreateBleed);
        $topCropRemoveBleed = 0;

        $createWidth = self::FRAME_WIDTH + self::FRAME_GENERATION_BLEED;
        $createHeight = self::FRAME_HEIGHT + self::FRAME_GENERATION_BLEED;

        $frameWindowLeft = imagecreatetruecolor($createWidth, $createHeight);
        $frameWindowRight = imagecreatetruecolor($createWidth, $createHeight);

        $trans_colour = imagecolorallocatealpha($frameWindowLeft, 0, 0, 0, 127);
        imagefill($frameWindowLeft, 0, 0, $trans_colour);
        imagealphablending($frameWindowLeft, true);
        imagesavealpha($frameWindowLeft, true);

        $trans_colour = imagecolorallocatealpha($frameWindowRight, 0, 0, 0, 127);
        imagefill($frameWindowRight, 0, 0, $trans_colour);
        // TODO cant sent both of these one has to be off
        imagealphablending($frameWindowRight, true);
        imagesavealpha($frameWindowRight, true);

        // LEFT BG

        /*
         * left placement -40x -20 y
         * right placement 0, -20
         * left text -15x , -20y
         * right text 5x, -20y
         */

        // this does the image shift
        $destXPosition = -1 * (self::FRAME_BLEED + $frameCreateBleed) ; // evals to 20 - 17 23
        $destYPosition = $topCropRemoveBleed;
        
        imagecopyresampled(
            $frameWindowLeft, $frameImage,
            $destXPosition, $destYPosition,
            0, 0,
            $srcFrameWidth, imagesy($frameWindowLeft),
            $srcFrameWidth, $srcFrameHeight
        );

        $this->log(__METHOD__ . __LINE__ . " SHANE we have created as $createWidth, $createHeight put to and size  $destXPosition, $destYPosition  $srcFrameWidth, $srcFrameHeight ");


        imagecopyresampled(
            $frameWindowRight, $frameImage,
            0, $topCropRemoveBleed,
            0, 0,
            $srcFrameWidth, imagesy($frameWindowRight),
            $srcFrameWidth, $srcFrameHeight
        );


        $frameWidth = imagesx($frameText);
        $otherFrameWidth = imagesx($frameWindowLeft);
        $frameHeight = imagesy($frameText);

        $this->log(__METHOD__ . " SHANE $frameWidth f $otherFrameWidth ");
        $textShift = $this->reelConfiguration['text_pixel_shift'];
        $textShift =  (self::FRAME_BLEED/2) + $textShift + $frameCreateBleed; // ~15 -- needs  to be 18 12

        imagecopyresampled($frameWindowLeft, $frameText,
            -1 * $textShift, $topCropRemoveBleed,
            0, 0,
            $srcFrameWidth, imagesy($frameWindowLeft),
            $srcFrameWidth, $srcFrameHeight
        );


        $dstXPosition = -1 * ((self::FRAME_BLEED/2) - $this->reelConfiguration['text_pixel_shift'] - $frameCreateBleed); // augmented by 3 + / -
        imagecopyresampled(
            $frameWindowRight, $frameText,
            $dstXPosition , $topCropRemoveBleed,
            0, 0,
            $srcFrameWidth, imagesy($frameWindowRight),
            $srcFrameWidth, $srcFrameHeight
        );

        imagedestroy($frameText);
        imagedestroy($frameImage);

        // shane rotate
        $rotateLeftAmount =  $this->reelConfiguration['frames'][$frameNumber]['rotate'];
        $rotateRightAmount =  $this->reelConfiguration['frames'][$frameNumber+7]['rotate'] ;

     //   $rotateLeftAmount = $rotateRightAmount = $rotate = $this->_getFrameRotate($frameNumber);

        if($frameNumber > 1 && $rotateLeftAmount > 0) {

            Varien_Profiler::start(__METHOD__ . "::Rotate left $frameNumber");

            $frameWindowLeft = imagerotate($frameWindowLeft, $rotateLeftAmount, $trans_colour,0);

            Varien_Profiler::stop(__METHOD__ . "::Rotate left $frameNumber");
        }

        if($frameNumber > 1 && $rotateRightAmount > 0) {

            Varien_Profiler::start(__METHOD__ . "::Rotate right $frameNumber");

            $frameWindowRight = imagerotate($frameWindowRight, $rotateRightAmount, $trans_colour, 0);

            Varien_Profiler::stop(__METHOD__ . "::Rotate right $frameNumber");
        }

        // clean it up
//        if($old != $frameWindow) {
//            imagedestroy($old);
//        }

        $this->debugWriteAllImages($frameWindowLeft, "frame{$frameNumber}_L.png");
        $this->debugWriteAllImages($frameWindowRight, "frame{$frameNumber}_R.png");

        return array($frameWindowLeft, $frameWindowRight);
    }

    function _generate_frame_viewport($frameNumber, $image, $text)
    {
        die(__FILE__ . " deprecated");

        if($this->_frame_data && isset($this->_frame_data[$frameNumber-1]) && isset($this->_frame_data[$frameNumber-1]['`']) && $this->_frame_data[$frameNumber-1]['viewport_file']) {
            $this->log(__METHOD__ . " using cached viewport!!! $frameNumber ");
            return $this->_create_from_png($this->getMediaPath() . '/' . $this->_frame_data[$frameNumber-1]['viewport_file']);
        }

        Varien_Profiler::start(__METHOD__ . " :: NO CACHED VIEWPORT Frame Number: $frameNumber ");


        $frameImage = $this->_create_from_png($image);
        $frameText = $this->_create_from_png($text);

        $frameWidth = imagesx($frameImage);
        $frameHeight = imagesy($frameImage);

        $viewportWidth = $this->reelConfiguration['frame_windows_width'];
        $viewportHeight = $frameHeight; // $this->reelConfiguration['frame_height'];

        $frameWindow = imagecreatetruecolor($viewportWidth + (self::FRAME_BLEED*2) , $viewportHeight);
        $viewportWidth = imagesx($frameWindow);
        $viewportHeight = imagesy($frameWindow);

        $trans_colour = imagecolorallocatealpha($frameWindow, 255, 255, 255, 127);
        imagefill($frameWindow, 0, 0, $trans_colour);
        imagealphablending($frameWindow, true);
        imagesavealpha($frameWindow, true);

        // LEFT BG

        imagecopy($frameWindow, $frameImage, 0, 0, 0, 0, $frameWidth, $frameHeight);

        // RIGHT BG
        $rightPlacement = $viewportWidth-$frameWidth;
        imagecopy($frameWindow, $frameImage, $rightPlacement, 0, 0, 0, $frameWidth, $frameHeight);


        $frameWidth = imagesx($frameText);
        $frameHeight = imagesy($frameText);

        $textShift = $this->reelConfiguration['text_pixel_shift'];

        imagecopy($frameWindow, $frameText, $textShift, 0, 0, 0, $frameWidth, $frameHeight);
        //RIGHT TXT
        imagecopy($frameWindow, $frameText, $rightPlacement-$textShift, 0, 0, 0, $frameWidth, $frameHeight);

        $this->debugWriteAllImages($frameWindow, "viewport{$frameNumber}.png");

        imagedestroy($frameText);
        imagedestroy($frameImage);

        $rotate = $this->_getFrameRotate($frameNumber);
        $old = $frameWindow;

        if($frameNumber > 1 && $rotate > 0) {

            Varien_Profiler::start(__METHOD__ . "::Rotate $frameNumber");

            $frameWindow = imagerotate($frameWindow, 360-$rotate, $trans_colour,0);

            $this->debugWriteAllImages($frameWindow, "viewport_rotated-{$frameNumber}.png");

            Varien_Profiler::stop(__METHOD__ . "::Rotate $frameNumber");
        }

        // clean it up
        if($old != $frameWindow) {
            imagedestroy($old);
        }

        return $frameWindow;

    }

    function _place_frame_viewport($background, $frameWindow, $frameNumber)
    {
        Varien_Profiler::start(__METHOD__ . " :: Frame Number: $frameNumber ");


        $bgWidth = imagesx($background);
        $bgHeight = imagesy($background);
        $fWidth = imagesx($frameWindow);
        $fHeight = imagesy($frameWindow);
        $dstX = ($bgWidth-$fWidth)/2;
        $dstY = ($bgHeight-$fHeight)/2;

        imagecopy($background, $frameWindow, $dstX, $dstY, 0, 0, $fWidth, $fHeight);

        imagedestroy($frameWindow);
        $this->debugWriteAllImages($background, "viewport_merged-{$frameNumber}.png");

        Varien_Profiler::stop(__METHOD__ . " :: Frame Number: $frameNumber ");

        return $background;
    }

    function _add_frame_image($background, $frameNumber, $image, $text)
    {
        throw new exception("this is deprecated due to _place_frame_viewport ");
        Varien_Profiler::start(__METHOD__ . " :: Frame Number: $frameNumber ");

        Varien_Profiler::start(__METHOD__ . " :: Create L&R frames : $frameNumber ");
        $frameImages = array(
            'L' => $this->_create_frame_image($image, $text, 1),
            'R' => $this->_create_frame_image($image, $text, 0)
        );

        $this->debugWriteAllImages($frameImages['L'], $frameNumber . '-L-frame.png');
        $this->debugWriteAllImages($frameImages['R'], $frameNumber . '-R-frame.png');


        Varien_Profiler::stop(__METHOD__ . " :: Create L&R frames : $frameNumber ");

        // we always use L frame cords.
        $rotateEachFrame = false;

        $window =  $this->_create_frame_window($frameImages, $frameNumber, $background);
        $this->debugWriteAllImages($window, "frame{$frameNumber}.png");

        $bgWidth = imagesx($background);
        $bgHeight = imagesy($background);
        $fWidth = imagesx($window);
        $fHeight = imagesy($window);
        $dstX = ($bgWidth-$fWidth)/2;
        $dstY = ($bgHeight-$fHeight)/2;

        imagecopy($background, $window, $dstX, $dstY, 0, 0, $fWidth, $fHeight);
        imagealphablending($background, true);
        imagesavealpha($background, true);


        Varien_Profiler::stop(__METHOD__ . " :: Frame Number: $frameNumber ");

        return $background;
    }

    public function logProfilerData()
    {
// disable profiler data for now
return '';
        $timers = Varien_Profiler::getTimers();
        $timerOut = '';
        foreach ($timers as $name=>$timer) {
            $sum = number_format(Varien_Profiler::fetch($name,'sum'), 4);
            $count = Varien_Profiler::fetch($name,'count');
            $realmem = number_format(Varien_Profiler::fetch($name,'realmem'));
            $emalloc = number_format(Varien_Profiler::fetch($name,'emalloc'));
            if ($sum<.0010 && $count<10 && $emalloc<10000) {
                continue;
            }
            $timerOut .= "$name:\t(sum: {$sum})\t(count: {$count})\t(Mem: {$emalloc})\t(RealMem: {$realmem}) \n ";
        }

        $sqlOut = Varien_Profiler::getSqlProfiler(Mage::getSingleton('core/resource')->getConnection('core_write'));
        $timerOut .= print_r(str_replace( "<br>", "\n", $sqlOut), 1);
        $sqlOut = false;


        $this->log(__CLASS__  . ": Script Profile Data :\n " . $timerOut );

        if($this->starttime !== false) {

            $mem = $this->convertSysMem(memory_get_usage(true));
            $this->log(__CLASS__  . ": Memory usage.. $mem", 0);

            $mem = $this->convertSysMem(memory_get_peak_usage(true));
            $this->log(__CLASS__  . ": Peak Memory usage.. $mem", 0);


            $mtime = microtime();
            $mtime = explode(" ",$mtime);
            $mtime = $mtime[1] + $mtime[0];
            $endtime = $mtime;
            $totaltime = ($endtime - $this->starttime);

            $this->log(__CLASS__  . ": This page was created in {$totaltime} seconds", 0);

        }

    }

    public function imagealphamask( &$picture, $mask ) {
        // Get sizes and set up new picture
        $xSize = imagesx( $picture );
        $ySize = imagesy( $picture );
        $newPicture = imagecreatetruecolor( $xSize, $ySize );
        imagesavealpha( $newPicture, true );
        imagefill( $newPicture, 0, 0, imagecolorallocatealpha( $newPicture, 0, 0, 0, 127 ) );

        // Resize mask if necessary
        if( $xSize != imagesx( $mask ) || $ySize != imagesy( $mask ) ) {
            $tempPic = imagecreatetruecolor( $xSize, $ySize );
            imagecopyresampled( $tempPic, $mask, 0, 0, 0, 0, $xSize, $ySize, imagesx( $mask ), imagesy( $mask ) );
            imagedestroy( $mask );
            $mask = $tempPic;
        }

        // Perform pixel-based alpha map application
        for( $x = 0; $x < $xSize; $x++ ) {
            for( $y = 0; $y < $ySize; $y++ ) {
                $alpha = imagecolorsforindex( $mask, imagecolorat( $mask, $x, $y ) );
                $alpha = 127 - floor( $alpha[ 'red' ] / 2 );
                $color = imagecolorsforindex( $picture, imagecolorat( $picture, $x, $y ) );
                imagesetpixel( $newPicture, $x, $y, imagecolorallocatealpha( $newPicture, $color[ 'red' ], $color[ 'green' ], $color[ 'blue' ], $alpha ) );
            }
        }

        // Copy back to original picture
        imagedestroy( $picture );
        $picture = $newPicture;
    }
}
