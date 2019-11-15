<?php

/**
 * Collinsharper/Chcustomform/controllers/IndexController.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chcustomform
 * @author    KL <support@collinsharper.com>
 * @copyright 2017 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */

/**
 * Collinsharper Custom Form Extension IndexController Class
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chaddtocart
 * @author    KL <support@collinsharper.com>
 * @copyright 2017 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Chcustomform_IndexController extends Mage_Core_Controller_Front_Action {
    
    /*
     * postMailAction is used to control form data, validation and send mail functionality
     */
    public function postMailAction() {
        if ($data = $this->getRequest()->getPost()) {
            try {
                $helper = Mage::helper('chcustomform');
                $gCaptchaEnable = $helper->getGoogleRecaptchaEnable();
                if($gCaptchaEnable == 1){
                    $recaptchResponse = trim($data['g-recaptcha-response']);
                    if(empty($recaptchResponse)) {
                        throw new Exception('Please enter captcha validation.');
                    } else {
                        if(Mage::helper('chcustomform')->validateGoogleRecaptcha($recaptchResponse)===false) {
                            throw new Exception('Captcha authentication is failed. Please try again.');
                        }
                    }
                }
                $to = Mage::helper('core')->decrypt($data['mailto']);
                $mailInitNum = $helper->getMailAutoIncNumber(); //Get auto increment field value for mail from admin panel
                $autoNum = $data['autonum']; // Get value of "auto_increment_num" field from custom form
                $subject = $data['formtitle']; 
                $mailContent = '<html><body><table cellspacing="0">';
                unset($data['mailto']);
                unset($data['g-recaptcha-response']);
                unset($data['formtitle']);
                unset($data['autonum']);
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $value = implode(",", $value);
                    }
                    $mailContent .= "<tr><th>$key: </th><td>$value</td></tr>";
                };
                $mailContent .= '</table></body></html>';
                $mail = Mage::getModel('core/email');
                $mail->setToEmail($to);
                $mail->setBody($mailContent);
                $mail->setSubject($subject);

                // Check if "auto_increment_num" is true or false and increment number based on that
                if ($mailInitNum) {
                    if ($autoNum == 'true') {
                        $mail->setSubject('[' . $mailInitNum . '] : ' . $subject);
                        $mailAutoIncNum = ($mailInitNum + 1);
                        Mage::getModel('core/config')->saveConfig('chcustomform/chcustomform_group/mail_init_num', $mailAutoIncNum);
                        //Code for retrieving uncached system configuration values 
                        Mage::getConfig()->reinit();
                        Mage::app()->reinitStores();
                    }
                }
                $mail->setFromEmail(Mage::getStoreConfig('trans_email/ident_general/email'));
                $mail->setFromName(Mage::getStoreConfig('trans_email/ident_general/name'));
                $mail->setType('html');// YOu can use Html or text as Mail format
                try {
                    $mail->send();
                    Mage::getSingleton('core/session')->addSuccess('Your request has been sent.');
                } catch (Exception $ex) {
                    Mage::getSingleton('core/session')->addError($ex->getMessage());
                }
            } catch (Exception $ex) {
                Mage::getSingleton('core/session')->addError($ex->getMessage());
            }
        } else {
            Mage::getSingleton('core/session')->addError('Invalid Request.');
        }
        $this->_redirectReferer();
    }
}
