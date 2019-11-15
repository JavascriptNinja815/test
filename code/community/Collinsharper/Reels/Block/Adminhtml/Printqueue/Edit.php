<?php
/**
 * Collinsharper/Reels/Block/Adminhtml/Printqueue/Edit.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Reels
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper_Reels_Block_Adminhtml_Printqueue_Edit
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Reels
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Reels_Block_Adminhtml_Printqueue_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'chreels';
        $this->_controller = 'adminhtml_printqueue';

        $this->_updateButton('save', 'label', Mage::helper('chreels')->__('Save Queue Information'));
        $this->_updateButton('delete', 'label', Mage::helper('chreels')->__('Delete Queue Information'));

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
        if( Mage::registry('printqueue_data') && Mage::registry('printqueue_data')->getId() ) {
            return Mage::helper('chreels')->__("Edit Queue '%s'", $this->htmlEscape(Mage::registry('printqueue_data')->getId()));
        } else {
            return Mage::helper('chreels')->__('Add Queue');
        }
    }
}