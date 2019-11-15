<?php

/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).

 * The Open Software License is available at this URL: 
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * DISCLAIMER
 * 
 * By adding to, editing, or in any way modifying this code, Sweet Tooth is 
 * not held liable for any inconsistencies or abnormalities in the 
 * behaviour of this code. 
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Sweet Tooth, outlined in the 
 * provided Sweet Tooth License. 
 * Upon discovery of modified code in the process of support, the Licensee 
 * is still held accountable for any and all billable time Sweet Tooth spent 
 * during the support process.
 * Sweet Tooth does not guarantee compatibility with any other framework extension. 
 * Sweet Tooth is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to 
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy 
 * immediately.
 * 
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Image Controller
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */

class TBT_Rewards_ImageController extends Mage_Core_Controller_Front_Action 
{
    const DEFAULT_FONT_FILE = 'fonts/Arimo-Bold-Latin.ttf';
    const DEFAULT_FONT_SIZE = 12;

    public function indexAction() 
    {
        $points = $this->getRequest ()->get("quantity");
        $currency_id = $this->getRequest()->get("currency");
        $currency = Mage::getModel('rewards/currency')->load($currency_id);
        $design = Mage::getDesign();

        $image = $design->getFilename($currency->getImage(), array('_type' => 'skin' ));
        $fontFileName = $currency->getFont();
        if (empty($fontFileName)) {
            $fontFileName = self::DEFAULT_FONT_FILE;
        }
        
        $font = $design->getFilename($fontFileName, array('_type' => 'skin'));
        $doPrintQty = (int) $currency->getImageWriteQuantity() === 1;
        $imageHeight = (int) $currency->getImageHeight();
        $imageWidth = (int) $currency->getImageWidth();
        $fontColor = (int) $currency->getFontColor();
        $fontSize = (int) $currency->getFontSize();

        if ($fontSize <= 0) {
            $fontSize = self::DEFAULT_FONT_SIZE;
        }

        $im = imageCreateFromPNG($image);
        $black = imagecolorallocate ($im, 0x00, 0x00, 0x00);

        // Path to our ttf font file
        $font_file = $font;
        
        list ($width, $height) = getimagesize($image);
        if (!$width || !$height) {
            $message = $this->__('You entered an invalid Image Path.');
            Mage::getSingleton('adminhtml/session')->addError($message);
        }

        if ($imageHeight > 0 && $imageWidth > 0) {
            $newwidth = $imageWidth;
            $newheight = $imageHeight;

            // Load
            $resized_im = imagecreatetruecolor($newwidth, $newheight);
            imagealphablending($resized_im, false);
            imagesavealpha($resized_im, true);
            $source = imagecreatefrompng($image);

            // Resize
            imagecopyresized($resized_im, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            imagealphablending($resized_im, true);
            $im = $resized_im;
        }

        //TODO: Externilize customization
        // Draw the text 'PHP Manual' using font size 13
        $img_h = ($imageHeight == 0) ? imagesy ( $im ) : $imageHeight;
        $img_w = imagesx ($im);
        $font_size = $fontSize;
        $text_color = $fontColor;
        $text = (empty($points) ? "" : (int) $points);

        $offsetx = $currency->getTextOffsetX();
        $offsety = $currency->getTextOffsetY();

        if (empty($offsetx)) {
            $offsetx = round(($img_w / 2) - (strlen($text) * imagefontwidth($font_size)) / 2 - 3, 1);
            if ((int) ($text) > 99) {
                $offsetx += 1;
            }
        }
        
        if (empty($offsety)) {
            $offsety = round(($img_h / 2) + imagefontheight($font_size) / 2, 1);
        }

        if ($doPrintQty) {
            imagefttext($im, $font_size, 0, $offsetx, $offsety, $text_color, $font_file, $text);
        }

        // Output image to the browser
        $this->getResponse()->setHeader('Content-Type', 'image/png', true);

        imagepng($im);
        imagedestroy($im);
    }
}
