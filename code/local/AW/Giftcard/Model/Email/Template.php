<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Giftcard
 * @version    1.0.8
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */

//class AW_Giftcard_Model_Email_Template extends Mage_Core_Model_Email_Template
class AW_Giftcard_Model_Email_Template extends Aschroder_SMTPPro_Model_Email_Template
{
    const DEFAULT_EMAIL_TEMPLATE_PATH = 'aw_giftcard_email_template';

    public function prepareEmailAndSend(array $variables, $store)
    {
        $this->setDesignConfig(array('store' => $store->getId()));
        $templateData = $this->_getEmptyTemplateData();
        $templateData['store'] = $store;
        $templateData['store_name'] = $store->getName();

        $template = self::DEFAULT_EMAIL_TEMPLATE_PATH;
        if (array_key_exists('aw_gc_email_template', $variables)) {
            $template = $variables['aw_gc_email_template'];
        }

        $message = '';
        if (array_key_exists('aw_gc_message', $variables)) {
            $message = $variables['aw_gc_message'];
        }

        $balance = '';
        if (array_key_exists('balance', $variables)) {
            $balance = $variables['balance'];
        }

        $orderId = '';
        if (array_key_exists('order_id', $variables)) {
            $orderId = $variables['order_id'];
        }

        $giftcodesRenderBlock = Mage::helper('aw_giftcard')->getEmailGiftcodeItemsBlock();
        $images = [];
        if (array_key_exists('aw_gc_created_codes', $variables) && $orderId) {
            foreach ($variables['aw_gc_created_codes'] as $code){
                if($this->generateGiftcardImage($balance, $code, $message, $orderId)){
                    $images[] = $orderId . '/' . $code . '.jpg';
                }
            }
            $giftcodesRenderBlock->setImages($images);
            $templateData['is_multiple_codes'] = 1 < count($variables['aw_gc_created_codes']);
        }

        $templateData['giftcards'] = $giftcodesRenderBlock->toHtml();
        if (array_key_exists('aw_gc_recipient_name', $variables)) {
            $templateData['recipient_name'] = $variables['aw_gc_recipient_name'];
        }

        if (array_key_exists('aw_gc_recipient_email', $variables)) {
            $templateData['recipient_email'] = $variables['aw_gc_recipient_email'];
        }

        if (array_key_exists('aw_gc_sender_email', $variables)) {
            $templateData['sender_email'] = $variables['aw_gc_sender_email'];
        }

        if (array_key_exists('aw_gc_sender_name', $variables)) {
            $templateData['sender_name'] = $variables['aw_gc_sender_name'];
        }

        return $this->sendTransactional(
            $template,
            Mage::helper('aw_giftcard/config')->getEmailSender($store),
            $templateData['recipient_email'],
            $templateData['recipient_name'],
            $templateData
        );
    }

    protected function _getEmptyTemplateData()
    {
        return $templateData = array(
            'recipient_name'    => '',
            'recipient_email'   => '',
            'sender_email'      => '',
            'sender_name'       => '',
            'message'           => '',
            'giftcards'         => '',
            'balance'           => '',
            'store'             => '',
            'store_name'        => '',
            'is_multiple_codes' => false
        );
    }

    protected function generateGiftcardImage($balance, $code, $message, $orderId)
    {
        $imagePath = Mage::getBaseDir('media') . DS . 'pimgpsh_fullsize_distr.jpg';
        $im = imagecreatefromjpeg($imagePath);
        $black = imagecolorallocate($im, 51, 51, 51);
        $font = 'var/fonts/OpenSans-Bold.ttf';
        imagettftext($im, 25, 0, 150, 277, $black, $font, $balance);
        imagettftext($im, 25, 0, 390, 271, $black, $font, $code);

        $testIm = imagecreate(300, 300);
        $messageFontSize = 15;
        while ($messageFontSize > 5){
            $stringWidth = 13;
            while ($stringWidth > 0){
                $wrapMessage = wordwrap($message, $stringWidth, "\n", true);
                $messageText = imagettftext($testIm, $messageFontSize, 0, 42, 345, $black, $font, $wrapMessage);
                $wrapWidth = $messageText[2] - $messageText[0];
                $wrapHeight = $messageText[1] - $messageText[7];
                $stringWidth++;
                if($wrapWidth > 200 || strstr($wrapMessage, "\n") === false) $stringWidth = 0;
            }
            if($wrapHeight > 120){
                $messageFontSize--;
            }else{
                break;
            }
        }

        imagedestroy($testIm);

        imagettftext($im, $messageFontSize, 0, 42, 345, $black, $font, $wrapMessage);

        $giftPath = Mage::getBaseDir('media') . DS . 'giftcards';
        if(!is_dir($giftPath)) {
            mkdir($giftPath, 0775, true);
        }
        $giftOrderPath = $giftPath . DS . $orderId;
        if(!is_dir($giftOrderPath)) {
            mkdir($giftOrderPath, 0775, true);
        }
        $result = imagejpeg($im, $giftOrderPath . DS . $code .'.jpg', 70);
        imagedestroy($im);
        return $result;
    }
}
