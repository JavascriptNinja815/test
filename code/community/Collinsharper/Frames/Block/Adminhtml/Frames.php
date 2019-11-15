<?php
/**
 * Collinsharper/Frames/Block/Adminhtml/Frames.php
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
 * Collinsharper_Frames_Block_Adminhtml_Frames
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
class Collinsharper_Frames_Block_Adminhtml_Frames extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_frames';
        $this->_blockGroup = 'chframes';
        $this->_headerText = Mage::helper('chframes')->__('Manage Frames');
        $this->_addButtonLabel = Mage::helper('chframes')->__('Add Frames');
        parent::__construct();
    }
}