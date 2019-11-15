<?php

class Collinsharper_Ordericons_Block_Adminhtml_Chicons_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
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
        /* @var $model Collinsharper_Ordericons_Model_chicons */
        $model = Mage::registry('chordericons_data');

        /*
         * Checking if user have permissions to save information, for future use only
         *
        if ($this->_isAllowedAction('save')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }
        */
        $isElementDisabled = false;

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

        $fieldset = $form->addFieldset('chordericons_form', array('legend'=>Mage::helper('chordericons')->__('Information')));

        $fieldset->addType('chimage','Collinsharper_Ordericons_Block_Adminhtml_Chicons_Helper_Image');

        
        $fieldset->addField('name', 'text', array(
            'label'     => Mage::helper('chordericons')->__('Name'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'name',
        ));
		

        $fieldset->addField('image', 'chimage', array(
            'label'     => Mage::helper('chordericons')->__('Image'),
            'required'  => false,
            'name'      => 'image',
        ));


		$fieldset->addField('comment', 'editor', array(
            'label'     => Mage::helper('chordericons')->__('Comment'),
            //'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'comment',
            'style'     => 'width:700px; height: 500px',
            'state'     => 'html',
            'config'    => $wysiwygConfig,
            'wysiwyg'   => true,
        ));

        if ( Mage::getSingleton('adminhtml/session')->getchiconsData() ) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getchiconsData());
            Mage::getSingleton('adminhtml/session')->setchiconsData(null);
        } elseif ( Mage::registry('chordericons_data') ) {
            $form->setValues(Mage::registry('chordericons_data')->getData());
        }

        return parent::_prepareForm();
    }

    /**
     * Check permission for passed action
     *
     * @param string $action
     * @return bool
     */
    protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/chordericons');
    }
}
