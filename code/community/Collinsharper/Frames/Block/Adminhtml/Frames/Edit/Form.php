<?php
/**
 * Collinsharper/Frames/Block/Adminhtml/Frames/Edit/Form.php
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
 * Collinsharper_Frames_Block_Adminhtml_Frames_Edit_Form
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
class Collinsharper_Frames_Block_Adminhtml_Frames_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareLayout()
    {
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled() && ($block = $this->getLayout()->getBlock('head'))) {
            $block->setCanLoadTinyMce(true);
        }
        return parent::_prepareLayout();
    }

    protected function _prepareForm()
    {
        /* @var $model Collinsharper_Frames_Model_Frames */
        $model = Mage::registry('frames_data');

        $form = new Varien_Data_Form(array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig(array(
            'add_variables'             => false,
            'add_widgets'               => false,
            'files_browser_window_url'  => $this->getBaseUrl().'admin/cms_wysiwyg_images/index/')
        );

        $fieldset = $form->addFieldset('frames_form', array('legend'=>Mage::helper('chframes')->__('Frames information')));

        $fieldset->addField('frame_name', 'text', array(
            'label'     => Mage::helper('chframes')->__('Name'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'frame_name',
        ));

        if ( Mage::getSingleton('adminhtml/session')->getFramesData() )
        {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getFramesData());
            Mage::getSingleton('adminhtml/session')->setFramesData(null);
        } elseif ( Mage::registry('frames_data') ) {
            $form->setValues(Mage::registry('frames_data')->getData());
        }

        return parent::_prepareForm();
    }
}
