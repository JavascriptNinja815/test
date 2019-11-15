<?php
/**
 * Collinsharper/Reels/Block/Adminhtml/Printqueue/Edit/Tabs.php
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
 * Collinsharper_Reels_Block_Adminhtml_Printqueue_Edit_Tabs
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
class Collinsharper_Reels_Block_Adminhtml_Printqueue_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('printqueue_tabs');
		$this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('chreels')->__('Print Queue Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'     => Mage::helper('chreels')->__('Print Queue Information'),
            'title'     => Mage::helper('chreels')->__('Print Queue Information'),
            'content'   => $this->getLayout()->createBlock('chreels/adminhtml_printqueue_edit_tab_form')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }
}