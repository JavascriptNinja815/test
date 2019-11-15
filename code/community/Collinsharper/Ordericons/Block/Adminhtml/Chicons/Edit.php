<?php

class Collinsharper_Ordericons_Block_Adminhtml_Chicons_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'chordericons';
        $this->_controller = 'adminhtml_chicons';


        $this->_updateButton('save', 'label', Mage::helper('chordericons')->__('Save'));
        $this->_updateButton('delete', 'label', Mage::helper('chordericons')->__('Delete'));

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
    }

    public function getHeaderText()
    {
        if( Mage::registry('chiconsblock_data') && Mage::registry('chiconsblock_data')->getId() ) {
            return Mage::helper('chordericons')->__("Edit chicons '%s'", $this->htmlEscape(Mage::registry('chiconsblock_data')->getPlayername()));
        } else {
            return Mage::helper('chordericons')->__('Add chicons');
        }
    }
}
