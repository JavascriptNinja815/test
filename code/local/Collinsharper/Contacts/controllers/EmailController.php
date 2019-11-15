<?php

class Collinsharper_Contacts_EmailController extends Mage_Core_Controller_Front_Action
{

    public function postAction()
    {
        // Make sure the post type has been set or default specialform will be used
        $result = false;
        $post = new Varien_Object();
        $_data = $this->getRequest()->getPost();
        foreach ($_data as $key => $val) {
            $post->setData(str_replace(' ','_',strtolower($key)), $val);
        }

        Mage::helper('chcontacts')->debugLog(__FILE__." ".__LINE__." INFO: ".print_r($post, true), null, "contactForm.log");

        if ($post) {
            try {
                $translate = Mage::getSingleton('core/translate');
                /* @var $translate Mage_Core_Model_Translate */
                $translate->setTranslateInline(false);

                // Set Post type
                $post_type = "specialform";
                if (isset($post['type'])) {
                    $post_type = $post['type'];
                }

                Mage::helper('chcontacts')->debugLog(__FILE__." ".__LINE__." INFO: ".print_r($post, true), null, "contactForm.log");

                $result = Mage::getModel('chcontacts/mailer_' . $post_type)->send($post, $_FILES);

                if ($result) {
                    $translate->setTranslateInline(true);
                    Mage::getSingleton('customer/session')->addSuccess(Mage::helper('chcontacts')->__(
                        'Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'
                    ));
                }
            } catch (Collinsharper_Contacts_Exception $che) {
                $translate->setTranslateInline(true);
              mage::logException($che);
                mage::logException(new Exception('bad send '));

                Mage::getSingleton('customer/session')->addError($che->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
              mage::logException($e);
                mage::logException(new Exception('bad send '));

                $translate->setTranslateInline(true);
                Mage::getSingleton('customer/session')->addError(Mage::helper('chcontacts')->__('Unable to submit your request. Please try again later'));
            }
        }

        $jsonData = json_encode($result);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }

}
