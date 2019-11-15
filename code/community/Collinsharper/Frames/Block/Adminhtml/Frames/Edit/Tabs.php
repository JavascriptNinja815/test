<?php
/**
 * Collinsharper/Frames/Block/Adminhtml/Frames/Edit/Tabs.php
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
 * Collinsharper_Frames_Block_Adminhtml_Frames_Edit_Tabs
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
class Collinsharper_Frames_Block_Adminhtml_Frames_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('frames_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('chframes')->__('Frames Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'     => Mage::helper('chframes')->__('Frames Information'),
            'title'     => Mage::helper('chframes')->__('Frames Information'),
            'content'   => $this->getLayout()->createBlock('chframes/adminhtml_frames_edit_tab_form')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }
}