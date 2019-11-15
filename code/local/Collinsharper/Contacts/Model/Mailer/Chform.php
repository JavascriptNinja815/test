<?php

class Collinsharper_Contacts_Model_Mailer_Chform extends Collinsharper_Contacts_Model_Mailer
{
    const STEREO_EMAIL_TEMPLATE_ID = 'chcontacts_email_stereo_order_template';
    const MARKET_ESTIMATE_EMAIL_TEMPLATE_ID = 'chcontacts_email_market_estimate_template';
    const MARKET_ORDER_EMAIL_TEMPLATE_ID = 'chcontacts_email_market_order_template';

    const EMAIL_I3D_RECIPIENT = 'Image 3D';
    public function _getCustomTemplate($data)
    {
        if($data->tag_stereo_order) {
            $data->ch_email_template = self::STEREO_EMAIL_TEMPLATE_ID;
        }
        if($data->tag_b2b_estimate) {
            $data->ch_email_template = self::MARKET_ESTIMATE_EMAIL_TEMPLATE_ID;
        }
        if($data->tag_b2b_order) {
            $data->ch_email_template = self::MARKET_ORDER_EMAIL_TEMPLATE_ID;
        }

        if(!$data->ch_email_template) {
             mage::log(__METHOD__ .  __LINE__ . " no template");
            return false;
        }

        return $data->ch_email_template;

    }


    public function _addOrderDetails(&$dataObj)
    {
        $inquiry = false;
        if($dataObj->inquiry_id) {
            $inquiry  = Mage::getModel('chinquiry/inquiry')->load($dataObj->inquiry_id);
        }

        if(!$inquiry) {
            $dataObj->order_details_html = '';
            return;
        }

        $oldDesignArea = Mage::getDesign()->getArea();

        Mage::getDesign()->setArea(Mage_Core_Model_App_Area::AREA_ADMINHTML);

        $layout = Mage::app()->getLayout();

      //  $blockName = 'Mage_Adminhtml_Block_Sales_Order_View';
        $blockName = 'Mage_Core_Block_Template';

        $secondBlock = false;
        if($dataObj->tag_stereo_order) {
            $block = $layout->createBlock(
                $blockName,
                'org_order_info.btb_stereo' . rand(),
                array('template' => 'chcategoryforms/type/stereo.phtml')
            );
            $secondBlock =  $layout->createBlock(
                'Collinsharper_Inquiry_Block_Adminhtml_Inquiry_Edit_Tab_Images',
                'org_order_info.btb_stereo-images' . rand(),
                array('template' => 'chuploads/action.phtml')
            );
        } else if ($dataObj->tag_b2b_estimate) {
            $block = $layout->createBlock(
                $blockName,
                'org_order_info.btb_estimate' . rand(),
                array('template' => 'chcategoryforms/type/b2b_estimate.phtml')
            );
        } else if ($dataObj->tag_b2b_order) {
            $block = $layout->createBlock(
                $blockName,
                'org_order_info.btb_order' . rand() ,
                array('template' => 'chcategoryforms/type/b2b_order.phtml')
            );
        }


        $block->setInquiry($inquiry);

        Mage::getDesign()->setArea($oldDesignArea);

        $dataObj->order_details_html = $block->toHtml();
        if(!$dataObj->customer_name || $dataObj->customer_name == '') {
		$dataObj->customer_name = $dataObj->name;
	}
mage::log(__METHOD__ ." SHANE we have " . print_r( $dataObj,1));
        if($secondBlock) {
            $secondBlock->setInquiry($inquiry);
            $list = $secondBlock->getFileUploads($inquiry);
            $imageData = '';

            $baseUrl = str_replace('/media/','',Mage::getBaseUrl('media'));

            $template = "<a href=\"{$baseUrl}%s\" target=_blank>%s</a> - %s <br />\n";
            foreach($list as $file) {
                $imageData .= sprintf($template, str_replace('customer_uploads','customer_download', $file->GetData('filepath')), $file->GetData('filename'),$file->GetData('comment'));
            }

            if($imageData != '') {
                $dataObj->order_image_data = $imageData;
            }
        }
   }


    public function send($dataObj, $files=array())
    {
  //      mage::log(__METHOD__ . __LINE__ . " data " . serialize($dataObj));
        $result = false;

        try {


            $templatePath = $this->_getCustomTemplate($dataObj);
            if(!$templatePath) {
                mage::log(__METHOD__ . " Unable to find template path");
                return false;
            }

            // Send email to Store first
           $this->_addOrderDetails($dataObj);

            mage::log(__METHOD__ . __LINE__ . " shane " . print_r($dataObj,1));

            //$this->sendEmail($dataObj, Mage::getStoreConfig($templatePath), $files, $dataObj->email, $dataObj->name);
            $emails = array(
                $dataObj->name => $dataObj->email,
            self::EMAIL_I3D_RECIPIENT => Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT)
            );

            if($dataObj->tag_stereo_order && Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT_STEREO)) {
                $emails[self::EMAIL_I3D_RECIPIENT] = Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT_STEREO);
            }
            if($dataObj->tag_b2b_estimate && Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT_ESTIMATE)) {
                $emails[self::EMAIL_I3D_RECIPIENT] = Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT_ESTIMATE);
            }
            if($dataObj->tag_b2b_order && Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT_ORDER)) {
                $emails[self::EMAIL_I3D_RECIPIENT] = Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT_ORDER);
            }

            foreach($emails as $name => $email) {
                $dataObj->is_admin_email = true;
                if(self::EMAIL_I3D_RECIPIENT == $name) {
                    $dataObj->is_admin_email = true;
                }
                $this->sendEmail($dataObj, $templatePath, $files, $email, $name);
            }



            mage::log(__METHOD__ . __LINE__ );
            $result = true;
        } catch (Exception $error) {
            mage::log(__METHOD__ . __LINE__ . " exception " . $error->getMessage());
            Mage::helper('chcontacts')->debugLog(__FILE__." ".__LINE__." ERR: ".print_r($error->getMessage(), true), null, "contactForm.log");
        }

        return $result;
    }

}
