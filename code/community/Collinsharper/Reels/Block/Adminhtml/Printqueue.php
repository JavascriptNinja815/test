<?php
/**
 * Collinsharper/Reels/Block/Adminhtml/Printqueue.php
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
 * Collinsharper_Reels_Block_Adminhtml_Printqueue
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
class Collinsharper_Reels_Block_Adminhtml_Printqueue extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_printqueue';
        $this->_blockGroup = 'chreels';
        $this->_headerText = Mage::helper('chreels')->__('Manage Queue');
        $this->_addButtonLabel = Mage::helper('chreels')->__('Add Queue');
        parent::__construct();
    }
}