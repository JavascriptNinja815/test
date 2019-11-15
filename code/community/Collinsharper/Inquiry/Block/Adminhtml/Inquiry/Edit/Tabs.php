<?php
class Collinsharper_Inquiry_Block_Adminhtml_Inquiry_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('chinquiry_tabs');
      //  $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('chinquiry')->__('Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'     => Mage::helper('chinquiry')->__('Information'),
            'title'     => Mage::helper('chinquiry')->__('Information'),
            'content'   => $this->getLayout()->createBlock('chinquiry/adminhtml_inquiry_edit_tab_form')->toHtml(),
        ));

        $this->addTab('image_section', array(
            'label'     => Mage::helper('chinquiry')->__('Images'),
            'title'     => Mage::helper('chinquiry')->__('Images'),
            'content'   => $this->getLayout()->createBlock('chinquiry/adminhtml_inquiry_edit_tab_images')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }
}
