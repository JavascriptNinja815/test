<?php
/**
 * Collinsharper/Fraudblock/Block/Adminhtml/Fraudblock/Edit.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Fraudblock
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper_Fraudblock_Block_Adminhtml_Fraudblock_Edit
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Fraudblock
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Fraudblock_Block_Adminhtml_Fraudblock_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'fraudblock';
        $this->_controller = 'adminhtml_fraudblock';

        $this->_updateButton('save', 'label', Mage::helper('fraudblock')->__('Save'));
        $this->_updateButton('delete', 'label', Mage::helper('fraudblock')->__('Delete'));

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
        if( Mage::registry('fraudblock_data') && Mage::registry('fraudblock_data')->getId() ) {
            return Mage::helper('fraudblock')->__("Edit Fraudblock '%s'", $this->htmlEscape(Mage::registry('fraudblock_data')->getPlayername()));
        } else {
            return Mage::helper('fraudblock')->__('Add Fraudblock');
        }
    }
}