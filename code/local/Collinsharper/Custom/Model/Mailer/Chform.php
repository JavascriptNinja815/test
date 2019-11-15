<?php

class Collinsharper_Custom_Model_Mailer_Chform extends Collinsharper_Contacts_Model_Mailer_Chform
{
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
                array('template' => 'chcategoryforms/type/b2b_estimate_custom.phtml')
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
		$imageData .= sprintf($template, $file->GetData('filepath'), $file->GetData('filename'),$file->GetData('comment'));
            }

            if($imageData != '') {
                $dataObj->order_image_data = $imageData;
            }
        }
   }


}
