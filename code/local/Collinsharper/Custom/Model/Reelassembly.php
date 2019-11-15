<?php
class Collinsharper_Custom_Model_Reelassembly extends Collinsharper_Image3d_Model_Reelassembly
{
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

        $createWidth = self::FRAME_WIDTH + self::FRAME_GENERATION_BLEED;
        $createHeight = self::FRAME_HEIGHT + self::FRAME_GENERATION_BLEED;

        $frameWindow = imagecreatetruecolor($createWidth, $createHeight);
        $frameWindowRight = imagecreatetruecolor($createWidth, $createHeight);

        $trans_colour = imagecolorallocatealpha($frameWindow, 255, 255, 255, 127);
        imagefill($frameWindow, 0, 0, $trans_colour);
        imagealphablending($frameWindow, true);
        imagesavealpha($frameWindow, true);

        $trans_colour = imagecolorallocatealpha($frameWindowRight, 255, 255, 255, 127);
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

        $border = 0;
        while(imagecolorat($frameImage, $border, $border) == 0xFFFFFF) {
          $border++;
        }
        $frameImageCropWhitespace = imagecreatetruecolor(imagesx($frameImage)-($border*2), imagesy($frameImage)-($border*2));

        imagealphablending($frameImageCropWhitespace, false);
        $transparency = imagecolorallocatealpha($frameImageCropWhitespace, 0, 0, 0, 127);
        imagefill($frameImageCropWhitespace, 0, 0, $transparency);
        imagesavealpha($frameImageCropWhitespace, true);

        imagecopy($frameImageCropWhitespace, $frameImage, 0, 0, $border, $border, imagesx($frameImageCropWhitespace), imagesy($frameImageCropWhitespace));

        imagecopy($frameWindow, $frameImageCropWhitespace,
            $destXPosition, $destYPosition,
            0, 0,
            $srcFrameWidth, $srcFrameHeight);

        $this->log(__METHOD__ . __LINE__ . " SHANE we have created as $createWidth, $createHeight put to and size  $destXPosition, $destYPosition  $srcFrameWidth, $srcFrameHeight ");


        imagecopy($frameWindowRight, $frameImageCropWhitespace,
            0, $topCropRemoveBleed,
            0, 0,
            $srcFrameWidth, $srcFrameHeight);


        $frameWidth = imagesx($frameText);
        $otherFrameWidth = imagesx($frameWindow);
        $frameHeight = imagesy($frameText);

        $this->log(__METHOD__ . " SHANE $frameWidth f $otherFrameWidth ");
        $textShift = $this->reelConfiguration['text_pixel_shift'];
        $textShift =  (self::FRAME_BLEED/2) + $textShift + $frameCreateBleed; // ~15 -- needs  to be 18 12

        imagecopy($frameWindow, $frameText,
            -1 * $textShift, $topCropRemoveBleed,
            0, 0,
            $srcFrameWidth, $srcFrameHeight);


        $dstXPosition = -1 * ((self::FRAME_BLEED/2) - $this->reelConfiguration['text_pixel_shift'] - $frameCreateBleed); // augmented by 3 + / -
        imagecopy($frameWindowRight, $frameText,
            $dstXPosition , $topCropRemoveBleed,
            0, 0,
            $srcFrameWidth, $srcFrameHeight);

        imagedestroy($frameText);
        imagedestroy($frameImage);
        imagedestroy($frameImageCropWhitespace);

        // shane rotate
        $rotateLeftAmount =  $this->reelConfiguration['frames'][$frameNumber]['rotate'];
        $rotateRightAmount =  $this->reelConfiguration['frames'][$frameNumber+7]['rotate'] ;

     //   $rotateLeftAmount = $rotateRightAmount = $rotate = $this->_getFrameRotate($frameNumber);

        if($frameNumber > 1 && $rotateLeftAmount > 0) {

            Varien_Profiler::start(__METHOD__ . "::Rotate left $frameNumber");

            $frameWindow = imagerotate($frameWindow, $rotateLeftAmount, $trans_colour,0);

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

        $this->debugWriteAllImages($frameWindow, "frame{$frameNumber}_L.png");
        $this->debugWriteAllImages($frameWindowRight, "frame{$frameNumber}_R.png");

        return array($frameWindow, $frameWindowRight);
    }
}
