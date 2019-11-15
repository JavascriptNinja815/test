<?php
/**
 * Collinsharper/Frames/Block/Adminhtml/Frames/Edit.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Frames
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper_Frames_Block_Adminhtml_Frames_Edit
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Frames
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Frames_Block_Adminhtml_Frames_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'chframes';
        $this->_controller = 'adminhtml_frames';

        $this->_updateButton('save', 'label', Mage::helper('chframes')->__('Save Reel Information'));
        $this->_updateButton('delete', 'label', Mage::helper('chframes')->__('Delete Reel Information'));

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
        if( Mage::registry('frames_data') && Mage::registry('frames_data')->getId() ) {
            return Mage::helper('chframes')->__("Edit Reel '%s'", $this->htmlEscape(Mage::registry('frames_data')->getReelName()));
        } else {
            return Mage::helper('chframes')->__('Add Reel');
        }
    }
}