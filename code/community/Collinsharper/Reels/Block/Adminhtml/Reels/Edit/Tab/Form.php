<?php
/**
 * Collinsharper/Reels/Block/Adminhtml/Reels/Edit/Tab/Form.php
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
 * Collinsharper_Reels_Block_Adminhtml_Reels_Edit_Tab_Form
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
class Collinsharper_Reels_Block_Adminhtml_Reels_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
		/* @var $model Collinsharper_Reels_Model_Reels */
        $model = Mage::registry('reels_data');
		
		$form = new Varien_Data_Form();
		$this->setForm($form);
	  
        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig(array(
            'add_variables'             => false,
            'add_widgets'               => false,
            'files_browser_window_url'  => $this->getBaseUrl().'admin/cms_wysiwyg_images/index/')
        );

        $fieldset = $form->addFieldset('reels_form', array('legend'=>Mage::helper('chreels')->__('Reels information')));

        $fieldset->addType('chreels_image', 'Collinsharper_Reels_Block_Adminhtml_Renderer_Image');


        $fieldset->addField('reel_name', 'text', array(
            'label'     => Mage::helper('chreels')->__('Name'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'reel_name',
        ));


        $fieldset->addField('print_qty', 'text', array(
            'label'     => Mage::helper('chreels')->__('Print Qty'),
            'note'     => Mage::helper('chreels')->__('saving the reel with a value will queue for printing.'),
        //    'class'     => 'required-entry',
      //      'required'  => true,
            'name'      => 'print_qty',
            'default_value'      => 0,
            'value'      => 0,
        ));


        $fieldset->addField('imported_id', 'text', array(
            'label'     => Mage::helper('chreels')->__('Legacy Reel Id'),
        //    'class'     => 'required-entry',
    //        'required'  => true,
            'name'      => 'imported_id',
        ));

        $fieldset->addField('customer_id', 'text', array(
            'label'     => Mage::helper('chreels')->__('Customer Id'),
        //    'class'     => 'required-entry',
    //        'required'  => true,
            'name'      => 'customer_id',
        ));

        $fieldset->addField('reel_data', 'textarea', array(
            'label'     => Mage::helper('chreels')->__('Reel Data'),
       //     'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'reel_data',
        ));

        $fieldset->addField('status', 'select', array(
            'label'     => Mage::helper('chreels')->__('Status'),
        //    'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'status',
            'values' => Collinsharper_Reels_Model_Reels::getStatusOptions(),
     //       'values' => Mage::getSingleton('ibi/asendiapickstatus')->getOptionArray(),

        ));


        $fieldset->addField('is_public', 'select', array(
            'label'     => Mage::helper('chreels')->__('Public'),
           // 'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'is_public',
            'values' =>  array(
                0 => mage::helper('chreels')->__('No'),
                1 => mage::helper('chreels')->__('Yes'),
            ),


        ));

        $fieldset->addField('public_reel_path', 'text', array(
            'label'     => Mage::helper('chreels')->__('public_reel_path'),
           // 'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'public_reel_path',
        ));

        $fieldset->addField('final_reel_file', 'text', array(
            'label'     => Mage::helper('chreels')->__('final_reel_file'),
           // 'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'final_reel_file',
        ));

        $fieldset->addField('thumb', 'text', array(
            'label'     => Mage::helper('chreels')->__('thumb'),
           // 'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'thumb',
        ));


        $thumbNote = '';
        if($model->getData('final_reel_file')) {
            $thumbNote = "<a target=_blank href=\"" . ( strstr($model->getData('final_reel_file'), 'http') ?
                    $model->getData('final_reel_file') :
                    Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS  . $model->getData('final_reel_file') ) . "\">" . $model->getData('final_reel_file') . "</a>";

        }


        if($model->getData('thumb')) {
            $fieldset->addField('thumbimg', 'chreels_image', array(
                'label'     => Mage::helper('chreels')->__('Preview'),
                // 'class'     => 'required-entry',
                //   'required'  => true,
                'name'      => 'thumb',
                'index'      => 'thumb',
                'note'      => $thumbNote,
            ));
        }


        if(0 && $model->getData('final_reel_file')) {
            $fieldset->addField('link', 'link', array(
                'name'      => Mage::helper('core')->__('Full Reel Link'),
                'href'      => strstr($model->getData('final_reel_file'), 'http') ?
                    $model->getData('final_reel_file') :
                    Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS  . $model->getData('final_reel_file'),
                'value'     => Mage::helper('core')->__('Full Reel Link'),
                'label'     => Mage::helper('core')->__('Full Reel'),
                'title'     => Mage::helper('core')->__('Full Reel'),
            ));
        }

        $fieldset->addField('file_status', 'select', array(
            'label'     => Mage::helper('chreels')->__('File Status'),
           // 'class'     => 'required-entry',
         //   'required'  => true,
            'name'      => 'file_status',
            'values' => Collinsharper_Reels_Model_Reels::getFileStatusOptions(),

        ));

        if ( Mage::getSingleton('adminhtml/session')->getReelsData() ) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getReelsData());
            Mage::getSingleton('adminhtml/session')->setReelsData(null);
        } elseif ( Mage::registry('reels_data') ) {
            $form->setValues(Mage::registry('reels_data')->getData());
        }

        return parent::_prepareForm();
    }
}