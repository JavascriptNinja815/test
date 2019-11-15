<?php
class TBT_Rewards_Block_Adminhtml_Points_Import_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $helper = Mage::helper('rewards');
        $form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array(
                    'id' => $this->getRequest()->getParam('id'))
                ),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        $fieldset = $form->addFieldset('import_file', array (
            'legend' =>	$helper->__('Import File')
        ));

        $fieldset->addField('csvFile', 'file', array (
            'label'              => $helper->__('CSV File'),
            'name'               => "csvFile",				
            'required'           => true,
            'value'              => 'Uplaod',
            'after_element_html' => '<br/><br/><small>See <a target="_blank" href="http://support.magerewards.com/article/1549-import-export-points-from-csv-file">documentation</a> for details</small>',
            'tabindex' => 1
        ));

        $fieldset = $form->addFieldset('import_results', array (
            'legend' =>	$helper->__('Import Results')
        ));

        $adminUser = Mage::getSingleton('admin/session')->getUser();
        $email = $adminUser ?  $adminUser->getEmail() : "";

        $fieldset->addField('email', 'text', array(
            'label'     => $helper->__('Send results to:'),
            'class'     => 'required-entry validate-email',
            'required'  => true,
            'name'      => 'email',
            'value'     => $email,
            'disabled'	=> false,
            'tabindex'	=> 4
        ));

        return parent::_prepareForm();
    }
}
