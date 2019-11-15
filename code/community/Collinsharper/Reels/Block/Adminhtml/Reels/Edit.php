<?php
/**
 * Collinsharper/Reels/Block/Adminhtml/Reels/Edit.php
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
 * Collinsharper_Reels_Block_Adminhtml_Reels_Edit
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
class Collinsharper_Reels_Block_Adminhtml_Reels_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'chreels';
        $this->_controller = 'adminhtml_reels';

        $this->_updateButton('save', 'label', Mage::helper('chreels')->__('Save Reel Information'));
        $this->_updateButton('delete', 'label', Mage::helper('chreels')->__('Delete Reel Information'));

        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

 $this->_addButton('loginascustomer', array(
            'label'     => Mage::helper('adminhtml')->__('Log in Customer'),
            'onclick'   => 'window.open(\''.Mage::getModel('adminhtml/url')->getUrl('widgentologinadmin/', array('id' => Mage::registry('reels_data')->getCustomerId())).'\', \'customer\');',
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
        if( Mage::registry('reels_data') && Mage::registry('reels_data')->getId() ) {
            return Mage::helper('chreels')->__("Edit Reel '%s'", $this->htmlEscape(Mage::registry('reels_data')->getReelName()));
        } else {
            return Mage::helper('chreels')->__('Add Reel');
        }
    }
}
