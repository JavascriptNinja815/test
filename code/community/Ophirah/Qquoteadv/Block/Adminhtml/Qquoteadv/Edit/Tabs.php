<?php

class Ophirah_Qquoteadv_Block_Adminhtml_Qquoteadv_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('qquote_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('qquoteadv')->__('Quote view'));
    }


    private $parent;

    protected function _prepareLayout()
    {
        //get all existing tabs
        $this->parent = parent::_prepareLayout();
        $this->addTab('product', array(
            'label' => Mage::helper('qquoteadv')->__('Quote request'),
            'title' => Mage::helper('qquoteadv')->__('Quote request'),
            'content' => $this->getLayout()->createBlock('qquoteadv/adminhtml_qquoteadv_edit_tab_product')->toHtml(),
        ));
       $this->addTab('ch_bits', array(
            'label' => Mage::helper('qquoteadv')->__('Quote Uploads'),
            'title' => Mage::helper('qquoteadv')->__('Quote Uploads'),
            'content' => $this->getLayout()->createBlock('collinsharper_chcustomeruploads/adminhtml_sales_order_view_tab_quote')->toHtml(),
        ));

        return $this->parent;
    }
}
