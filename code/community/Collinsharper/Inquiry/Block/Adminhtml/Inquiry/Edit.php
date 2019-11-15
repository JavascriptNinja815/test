<?php
class Collinsharper_Inquiry_Block_Adminhtml_Inquiry_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'chinquiry';
        $this->_controller = 'adminhtml_inquiry';

        $this->_updateButton('save', 'label', Mage::helper('chinquiry')->__('Save'));
        $this->_updateButton('delete', 'label', Mage::helper('chinquiry')->__('Delete'));

        if( Mage::app()->getRequest()->getParam('id', false)) {
            $id =  Mage::app()->getRequest()->getParam('id');
            $url = $this->getUrl('*/*/updatestatus', array('id'=> $id, 'status_id' => Collinsharper_Inquiry_Model_Source_Status::STATE_ARCHIVED));
            $this->_addButton('markarchive', array(
                'label' => $this->__('Mark as Archived'),
                //'onclick' => "document.location.href = document.location.href.replace('/edit/',/markarchive/);"
                'onclick' => "setLocation('{$url}')",
            ));

            $url = $this->getUrl('*/*/updatestatus', array('id'=> $id, 'status_id' => Collinsharper_Inquiry_Model_Source_Status::STATE_VIEWED));
            $this->_addButton('markread', array(
                'label' => $this->__('Mark as Read'),
                //'onclick' => "document.location.href = document.location.href.replace('/edit/',/markread/);"
                'onclick' => "setLocation('{$url}')",
            ));


            $url = $this->getUrl('*/*/sendemail', array('id'=> $id));
            $this->_addButton('sendemail', array(
                'label' => $this->__('Resend Email'),
                //'onclick' => "document.location.href = document.location.href.replace('/edit/',/markread/);"
                'onclick' => "setLocation('{$url}')",
            ));
        }

                $this->_addButton('saveandcontinue', array(
                    'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
                    'onclick'   => 'saveAndContinueEdit()',
                    'class'     => 'save',
                ), -100);
                // Use this to include any Javascript
                $this->_formScripts[] = "
                    function saveAndContinueEdit(){
                        editForm.submit($('edit_form').action+'back/edit/');
                    }
                ";

    //   $this->_removeButton('save');
    }

    public function getHeaderText()
    {
        if( Mage::registry('chinquiry_data') && Mage::registry('chinquiry_data')->getId() ) {
            return Mage::helper('chinquiry')->__("Review Inquiry '%s'", $this->htmlEscape(Mage::registry('chinquiry_data')->getEmail()));
        } else {
            return Mage::helper('chinquiry')->__('Add Fraudblock');
        }
    }
}

