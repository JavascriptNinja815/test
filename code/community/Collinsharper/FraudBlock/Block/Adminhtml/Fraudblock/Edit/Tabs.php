<?php
/**
 * Collinsharper/Fraudblock/Block/Adminhtml/Fraudblock/Edit/Tabs.php
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
 * Collinsharper_Fraudblock_Block_Adminhtml_Fraudblock_Edit_Tabs
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
class Collinsharper_Fraudblock_Block_Adminhtml_Fraudblock_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('fraudblock_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('fraudblock')->__('Fraudblock Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'     => Mage::helper('fraudblock')->__('Fraudblock Information'),
            'title'     => Mage::helper('fraudblock')->__('Fraudblock Information'),
            'content'   => $this->getLayout()->createBlock('fraudblock/adminhtml_fraudblock_edit_tab_form')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }
}