<?php

class Collinsharper_Contacts_Model_Mailer extends Collinsharper_Contacts_Model_Abstract
{
    const XML_PATH_EMAIL_RECIPIENT  = 'contacts/specialcontact/recipient_email';
    const XML_PATH_EMAIL_RECIPIENT_STEREO  = 'contacts/specialcontact/recipient_email_stereo';
    const XML_PATH_EMAIL_RECIPIENT_ESTIMATE  = 'contacts/specialcontact/recipient_email_market_btb';
    const XML_PATH_EMAIL_RECIPIENT_ORDER  = 'contacts/specialcontact/recipient_email_market_order';
    const XML_PATH_EMAIL_SENDER     = 'contacts/specialcontact/sender_email_identity';

    public function sendEmail($dataObj, $templateId, $files=array(), $recipientEmail = null, $recipientName = "")
    {
            $replyEmail = Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT);
        if ($recipientEmail == null) {
            $recipientEmail = Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT);
        }

        $storeId = Mage::app()->getStore()->getId();

        $emailTemplate = Mage::getModel('core/email_template')
            ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId));

        if(count($files)) {
            $this->_createAttachments($emailTemplate, $files);
        }

        $senderEmail = Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER, $storeId);

        $emailTemplateData = array();


        if($dataObj->shipping) {
            $dataObj->customer_name = $dataObj->shipping['firstname'] . ' ' . $dataObj->shipping['lastname'];
        }

        // b2b name is different, update it here.

        $emailTemplateData['data'] = $dataObj;

       mage::log(__METHOD__ . __LINE__ . " adding details to the object " . print_r($emailTemplateData,1));

        $emailTemplate
	    ->setReplyTo($replyEmail)
	    ->sendTransactional(
            $templateId,
            $senderEmail,
            $recipientEmail,
            $recipientName,
            $emailTemplateData
        );

        if (!$emailTemplate->getSentSuccess()) {
            Mage::helper('chcontacts')->debugLog(__FILE__." ".__LINE__." ERR: ".print_r($senderEmail, true), null, "contactForm.log");
            Mage::helper('chcontacts')->debugLog(__FILE__." ".__LINE__." ERR: ".print_r($recipientEmail, true), null, "contactForm.log");
            Mage::helper('chcontacts')->debugLog(__FILE__." ".__LINE__." ERR: ".print_r($recipientName, true), null, "contactForm.log");
            Mage::helper('chcontacts')->debugLog(__FILE__." ".__LINE__." ERR: ".print_r($emailTemplate, true), null, "contactForm.log");
            throw new Exception(Mage::helper('chcontacts')->__('Email was not sent successfully.'));
        }

        return $this;
    }

    protected function _createAttachments($emailTemplate, $files)
    {
        $helper = Mage::helper('chcontacts');
        $maxFileSize = 2000000;
        $allowedFileTypes = $helper->getAllowedMimeTypes();
        $numAttachments = 0;

        foreach ($files as $file) {
            // must be separate from switch statement because you can't continue a loop from inside switch
            if ($file['error'] == UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($file['error']) {
                switch ($file['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        // drop through
                    case UPLOAD_ERR_FORM_SIZE:
                        throw new Collinsharper_Contacts_Exception($helper->__('Invalid file size: %s (%s)', $file['name'], $file['size']));
                        break;
                    default:
                        throw new Exception($helper->__('File upload error: %s', $file['error']));
                }
            }

            if ($file['size'] > $maxFileSize) {
                throw new Collinsharper_Contacts_Exception($helper->__('Invalid file size: %s (%s)', $file['name'], $file['size']));
            }

            if (!in_array($file['type'], $allowedFileTypes)) {
                throw new Collinsharper_Contacts_Exception($helper->__('Invalid file type: %s (%s)', $file['name'], $file['type']));
            }

            $fileContents = file_get_contents($file['tmp_name']);
            if ($fileContents === false) {
                throw new Exception($helper->__('Could not read uploaded file contents.'));
            }
            $emailTemplate->getMail()->createAttachment(
                $fileContents,
                $file['type'],
                Zend_Mime::DISPOSITION_ATTACHMENT,
                Zend_Mime::ENCODING_BASE64,
                $file['name']
            );
            $numAttachments++;
        }

        return $numAttachments;
    }

}
