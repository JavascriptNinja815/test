<?php
/**
 * Collinsharper/Reels/Block/Adminhtml/Reels/Edit/Tabs.php
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
 * Collinsharper_Reels_Block_Adminhtml_Reels_Edit_Tabs
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
class Collinsharper_Reels_Block_Adminhtml_Reels_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('reels_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('chreels')->__('Reels Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label' => Mage::helper('chreels')->__('Reels Information'),
            'title' => Mage::helper('chreels')->__('Reels Information'),
            'content' => $this->getLayout()->createBlock('chreels/adminhtml_reels_edit_tab_form')->toHtml(),
        ));

        /*
         *
         * show frames here
		 */
        $this->addTab('frames_section', array(
            'label' => Mage::helper('chreels')->__('Images'),
            'title' => Mage::helper('chreels')->__('Images'),
            'url' => $this->getUrl('*/*/frames', array('_current' => true)),
            //'content'   => $this->getLayout()->createBlock('chreels/adminhtml_reels_edit_tab_frames')->toHtml(),
            'class' => 'ajax'
        ));


        return parent::_beforeToHtml();
    }

    protected function _updateActiveTab()
    {
        $tabId = $this->getRequest()->getParam('tab');
        if ($tabId) {
            $tabId = preg_replace("#{$this->getId()}_#", '', $tabId);
            if ($tabId) {
                $this->setActiveTab($tabId);
            }
        } else {
            $this->setActiveTab('form_section');
        }
    }
}