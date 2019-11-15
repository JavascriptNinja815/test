<?php
/**
 * Collinsharper/Reels/Block/Adminhtml/Printqueue/Edit/Form.php
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
 * Collinsharper_Reels_Block_Adminhtml_Printqueue_Edit_Form
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
class Collinsharper_Reels_Block_Adminhtml_Printqueue_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
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
        /* @var $model Collinsharper_Reels_Model_printqueue */
        $model = Mage::registry('printqueue_data');

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

        $fieldset = $form->addFieldset('printqueue_form', array('legend'=>Mage::helper('chreels')->__('Print Queue Information')));

        //$fieldset->addType('chreels_image', 'Collinsharper_Reels_Block_Adminhtml_Renderer_Image');


        $fieldset->addField('customer_id', 'text', array(
            'label'     => Mage::helper('chreels')->__('Customer Id'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'customer_id',
        ));


        $fieldset->addField('reel_id', 'text', array(
            'label'     => Mage::helper('chreels')->__('Reel Id'),
            'class'     => 'required-entry',
			'required'  => true,
            'name'      => 'reel_id',
            //'default_value'      => 0,
            //'value'      => 0,
        ));


        $fieldset->addField('print_filename', 'text', array(
            'label'     => Mage::helper('chreels')->__('Print Filename'),
        //    'class'     => 'required-entry',
    //        'required'  => true,
            'name'      => 'print_filename',
        ));

        $fieldset->addField('final_reel_filename', 'text', array(
            'label'     => Mage::helper('chreels')->__('Final Reel Filename'),
        //    'class'     => 'required-entry',
    //        'required'  => true,
            'name'      => 'final_reel_filename',
        ));

        $fieldset->addField('order_id', 'text', array(
            'label'     => Mage::helper('chreels')->__('Order Id'),
       //     'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'order_id',
        ));
		
        $fieldset->addField('order_item_id', 'text', array(
            'label'     => Mage::helper('chreels')->__('Order Item Id'),
       //     'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'order_item_id',
        ));

        $fieldset->addField('qty', 'text', array(
            'label'     => Mage::helper('chreels')->__('Quantity'),
       //     'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'qty',
        ));

        $fieldset->addField('status', 'select', array(
            'label'     => Mage::helper('chreels')->__('Status'),
        //    'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'status',
            'values' => Collinsharper_Reels_Model_Reels::getStatusOptions(),
     //       'values' => Mage::getSingleton('ibi/asendiapickstatus')->getOptionArray(),

        ));

        $fieldset->addField('note', 'textarea', array(
            'label'     => Mage::helper('chreels')->__('Note'),
           // 'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'note',
        ));

        if ( Mage::getSingleton('adminhtml/session')->getPrintqueueData() ) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getPrintqueueData());
            Mage::getSingleton('adminhtml/session')->setPrintqueueData(null);
        } elseif ( Mage::registry('printqueue_data') ) {
            $form->setValues(Mage::registry('printqueue_data')->getData());
        }

        return parent::_prepareForm();
    }
}
