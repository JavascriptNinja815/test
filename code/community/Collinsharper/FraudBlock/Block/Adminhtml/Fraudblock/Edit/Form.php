<?php
/**
 * Collinsharper/Fraudblock/Block/Adminhtml/Fraudblock/Edit/Form.php
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
 * Collinsharper_Fraudblock_Block_Adminhtml_Fraudblock_Edit_Form
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
class Collinsharper_Fraudblock_Block_Adminhtml_Fraudblock_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
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
        /* @var $model Collinsharper_Fraudblock_Model_Fraudblock */
        $model = Mage::registry('fraudblock_data');

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

        $fieldset = $form->addFieldset('fraudblock_form', array('legend'=>Mage::helper('fraudblock')->__('Fraudblock information')));

        $fieldset->addField('customer_id', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('Customer ID'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'customer_id',
        ));
		
        $fieldset->addField('ip', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('IP'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'ip',
        ));
		
        $fieldset->addField('email', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('Email'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'email',
        ));
		
        $fieldset->addField('domain_a', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('Domain 1'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'domain_a',
        ));
		
        $fieldset->addField('domain_b', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('Domain 2'),
            // //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'domain_b',
        ));
		
        $fieldset->addField('domain_c', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('Domain 3'),
            ////'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'domain_c',
        ));
		
        $fieldset->addField('browser_hash', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('Browser hash'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'browser_hash',
        ));
		
        $fieldset->addField('cc_hash_a', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('CC hash 1'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'cc_hash_a',
        ));
		
        $fieldset->addField('cc_hash_b', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('CC hash 2'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'cc_hash_b',
        ));
		
        $fieldset->addField('cc_hash_c', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('CC hash 3'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'cc_hash_c',
        ));
		
        $fieldset->addField('cc_hash_d', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('CC hash 4'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'cc_hash_d',
        ));
		
        $fieldset->addField('cc_hash_e', 'text', array(
            'label'     => Mage::helper('fraudblock')->__('CC hash 5'),
            //'class'     => 'required-entry',
            // 'required'  => true,
            'name'      => 'cc_hash_e',
        ));
		
		$fieldset->addField('banned', 'select', array(
            'label'     => Mage::helper('cms')->__('User is Banned?'),
            'title'     => Mage::helper('cms')->__('User is Banned?'),
            'name'      => 'banned',
            'required'  => true,
            'options'   => array(
                '1' => Mage::helper('cms')->__('Yes'),
                '0' => Mage::helper('cms')->__('No'),
            ),
        ));
		
        if (!$model->getId()) {
            $model->setData('banned', 1);
        }
		
		$fieldset->addField('comment', 'editor', array(
            'label'     => Mage::helper('fraudblock')->__('Comment'),
            //'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'comment',
            'style'     => 'width:700px; height: 500px',
            'state'     => 'html',
            'config'    => $wysiwygConfig,
            'wysiwyg'   => true,
        ));

        if ( Mage::getSingleton('adminhtml/session')->getFraudblockData() ) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getFraudblockData());
            Mage::getSingleton('adminhtml/session')->setFraudblockData(null);
        } elseif ( Mage::registry('fraudblock_data') ) {
            $form->setValues(Mage::registry('fraudblock_data')->getData());
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
        return Mage::getSingleton('admin/session')->isAllowed('sales/fraudblock');
    }
}
