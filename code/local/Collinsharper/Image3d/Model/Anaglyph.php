<?php
//***********************************************
//*         Copyright � Stefan Saftescu         *
//*          Bucharest, Romania, 2006           *
//*            All rights reserved.             *
//*         Free for non-comercial use.         *
//*                                             *
//*  More info about Stereo Anaglyph Images at  *
//* http://en.wikipedia.org/wiki/Anaglyph_image *
//***********************************************

/**
 * Please note this class may only be used for internal QA and testing purposes it
 */

class Collinsharper_Image3d_Model_Anaglyph extends Collinsharper_Image3d_Model_Abstract
{


    var $leftImg;
    var $rightImg;
    var $leftImgContents = false;
    var $rightImgContents = false;
    var $stereoImg = false;
    var $errors = array();
    var $error = false;
    var $i = 0;

    function setImages($left, $right)
    {
        $this->leftImg = $left;
        $this->rightImg = $right;
    }

    function loadImages()
    {
        if(!is_file($this->leftImg))
        {
            $this->errors[$this->i++] = $this->leftImg . " could not be found.";
            $this->error = true;
        }
        if(!is_file($this->rightImg))
        {
            $this->errors[$this->i++] = $this->rightImg . " could not be found.";
            $this->error = true;
        }
        if(!$this->error)
        {
            $this->leftImgContents = @imagecreatefromstring(file_get_contents($this->leftImg));
            if($this->leftImgContents === false)
            {
                $this->errors[$this->i++] = $this->leftImg . " is not a valid image.";
                $this->error = true;
            } else {
                imagealphablending($this->leftImgContents, false);
                imagesavealpha($this->leftImgContents, true);
                $this->rightImgContents = @imagecreatefromstring(file_get_contents($this->rightImg));
                if($this->leftImgContents === false)
                {
                    $this->errors[$this->i++] = $this->rightImg . " is not a valid image.";
                    $this->error = true;
                } else {
                    imagealphablending($this->rightImgContents, true);
                    imagesavealpha($this->rightImgContents, true);
                }
            }
        }
    }

    function &createStereoImage()
    {
        if(!$this->error)
        {


            $leftX = imagesx($this->leftImgContents);
            $leftY = imagesy($this->leftImgContents);
            $rightX = imagesx($this->rightImgContents);
            $rightY = imagesy($this->rightImgContents);

            if(($leftX != $rightX) || ($leftY != $rightY))
            {
                $this->errors[] = "The images aren't the same size.";
                $this->error = true;
                return $this->stereoImg; //false
            }
            $this->stereoImg = imagecreatetruecolor($leftX, $leftY);
            imagealphablending($this->stereoImg, false);
            imagesavealpha($this->stereoImg, true);
            for($x=1; $x<=$leftX; $x++)
            {
                for($y=1; $y<=$leftY; $y++)
                {
                    //the pixel's red channel of the stereo image will be the grayscale pixel of the left image
                    //0.299, 0.587 and 0.114 are corrections made for the human eye
                    // TODO: this fails to  allocate colors oten.. Im not sure why...
                    /*
                     *                     $r = min(255, max(0, //the red amount of color is limited between 0 and 255
                        ((imagecolorat($this->leftImgContents, $x, $y) >> 16) & 255) * 0.299 //red channel to grayscale
                            + ((imagecolorat($this->leftImgContents, $x, $y) >> 8) & 255) * 0.587 //green channel to grayscale
                            + ((imagecolorat($this->leftImgContents, $x, $y)) & 255) * 0.114)); // blue channel to grayscale

                     */
                    $r = min(255, max(0, //the red amount of color is limited between 0 and 255
                        ((@imagecolorat($this->leftImgContents, $x, $y) >> 16) & 255) * 0.299 //red channel to grayscale
                            + ((@imagecolorat($this->leftImgContents, $x, $y) >> 8) & 255) * 0.587 //green channel to grayscale
                            + ((@imagecolorat($this->leftImgContents, $x, $y)) & 255) * 0.114)); // blue channel to grayscale

                    //the pixel's green and blue channels will be the pixel's green and blue channel of the right image
                    $g = (@imagecolorat($this->rightImgContents, $x, $y) >> 8) & 255;
                    $b = (@imagecolorat($this->rightImgContents, $x, $y)) & 255;

                    //mix the red, green, and blue channels into a color
                    $color = imagecolorallocate($this->stereoImg, $r, $g, $b);

                    //finally, draw the pixel
                    imagesetpixel($this->stereoImg, $x, $y, $color);
                }
            }
        }
        return $this->stereoImg;
        @imagedestroy($this->rightImgContents);
        @imagedestroy($this->leftImgContents);
    }

}