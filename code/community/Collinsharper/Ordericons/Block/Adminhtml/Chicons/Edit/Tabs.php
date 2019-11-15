<?php

class Collinsharper_Ordericons_Block_Adminhtml_Chicons_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('chicons_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('chordericons')->__('Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'     => Mage::helper('chordericons')->__('Information'),
            'title'     => Mage::helper('chordericons')->__('Information'),
            'content'   => $this->getLayout()->createBlock('chordericons/adminhtml_chicons_edit_tab_form')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }
}