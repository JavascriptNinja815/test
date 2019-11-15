<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Adminhtml report filter form
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Report_Filter_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Report type options
     */
    protected $_reportTypeOptions = array();

    /**
     * Report field visibility
     */
    protected $_fieldVisibility = array();

    /**
     * Report field opions
     */
    protected $_fieldOptions = array();

    /**
     * Set field visibility
     *
     * @param string Field id
     * @param bool Field visibility
     */
    public function setFieldVisibility($fieldId, $visibility)
    {
        $this->_fieldVisibility[$fieldId] = (bool)$visibility;
    }

    /**
     * Get field visibility
     *
     * @param string Field id
     * @param bool Default field visibility
     * @return bool
     */
    public function getFieldVisibility($fieldId, $defaultVisibility = true)
    {
        if (!array_key_exists($fieldId, $this->_fieldVisibility)) {
            return $defaultVisibility;
        }
        return $this->_fieldVisibility[$fieldId];
    }

    /**
     * Set field option(s)
     *
     * @param string $fieldId Field id
     * @param mixed $option Field option name
     * @param mixed $value Field option value
     */
    public function setFieldOption($fieldId, $option, $value = null)
    {
        if (is_array($option)) {
            $options = $option;
        } else {
            $options = array($option => $value);
        }
        if (!array_key_exists($fieldId, $this->_fieldOptions)) {
            $this->_fieldOptions[$fieldId] = array();
        }
        foreach ($options as $k => $v) {
            $this->_fieldOptions[$fieldId][$k] = $v;
        }
    }

    /**
     * Add report type option
     *
     * @param string $key
     * @param string $value
     * @return Mage_Adminhtml_Block_Report_Filter_Form
     */
    public function addReportTypeOption($key, $value)
    {
        $this->_reportTypeOptions[$key] = $this->__($value);
        return $this;
    }

    /**
     * Add fieldset with general report fields
     *
     * @return Mage_Adminhtml_Block_Report_Filter_Form
     */
    protected function _prepareForm()
    {
        $actionUrl = $this->getUrl('*/*/sales');
        $form = new Varien_Data_Form(
            array('id' => 'filter_form', 'action' => $actionUrl, 'method' => 'get')
        );
        $htmlIdPrefix = 'sales_report_';
        $form->setHtmlIdPrefix($htmlIdPrefix);
        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('reports')->__('Filter')));

        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $timeFormatIso = 'hh:mm:ss';
        
        $fieldset->addField('store_ids', 'hidden', array(
            'name'  => 'store_ids'
        ));

        $fieldset->addField('report_type', 'select', array(
            'name'      => 'report_type',
            'options'   => $this->_reportTypeOptions,
            'label'     => Mage::helper('reports')->__('Match Period To'),
        ));

        $fieldset->addField('period_type', 'select', array(
            'name' => 'period_type',
            'options' => array(
                'hour'   => Mage::helper('reports')->__('Hour'),
                'day'   => Mage::helper('reports')->__('Day'),
                'month' => Mage::helper('reports')->__('Month'),
                'year'  => Mage::helper('reports')->__('Year')
            ),
            'label' => Mage::helper('reports')->__('Period'),
            'title' => Mage::helper('reports')->__('Period')
        ));

        $fieldset->addField('from', 'date', array(
            'name'      => 'from',
            'format'    => $dateFormatIso,
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'label'     => Mage::helper('reports')->__('From'),
            'title'     => Mage::helper('reports')->__('From'),
            'required'  => true
        ));

        $fieldset->addField('to', 'date', array(
            'name'      => 'to',
            'format'    => $dateFormatIso,
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'label'     => Mage::helper('reports')->__('To'),
            'title'     => Mage::helper('reports')->__('To'),
            'required'  => true
        ));

        if(strpos($_SERVER['REQUEST_URI'], 'admin/report_sales/sales') !== FALSE) {
	        $fieldset->addField('start_time', 'select', array(
	            'name'      => 'start_time',
	            'options'   => array(
	                '00:00:00'      => '12:00 AM',
	                '01:00:00'      => '1:00 AM',
	                '02:00:00'      => '2:00 AM',
	                '03:00:00'      => '3:00 AM',
	                '04:00:00'      => '4:00 AM',
	                '05:00:00'      => '5:00 AM',
	                '06:00:00'      => '6:00 AM',
	                '07:00:00'      => '7:00 AM',
	                '08:00:00'      => '8:00 AM',
	                '09:00:00'      => '9:00 AM',
	                '10:00:00'      => '10:00 AM',
	                '11:00:00'      => '11:00 AM',
	                '12:00:00'      => '12:00 PM',
	                '13:00:00'      => '1:00 PM',
	                '14:00:00'      => '2:00 PM',
	                '15:00:00'      => '3:00 PM',
	                '16:00:00'      => '4:00 PM',
	                '17:00:00'      => '5:00 PM',
	                '18:00:00'      => '6:00 PM',
	                '19:00:00'      => '7:00 PM',
	                '20:00:00'      => '8:00 PM',
	                '21:00:00'      => '9:00 PM',
	                '22:00:00'      => '10:00 PM',
	                '23:00:00'      => '11:00 PM'
	            ),
	            'label'     => Mage::helper('reports')->__('Start Time'),
	            'title'     => Mage::helper('reports')->__('Start Time'),
	            'required'  => true
	        ));
	
	        $fieldset->addField('end_time', 'select', array(
	            'name'      => 'end_time',
	            'options'   => array(
	                '00:00:00'      => '12:00 AM',
	                '01:00:00'      => '1:00 AM',
	                '02:00:00'      => '2:00 AM',
	                '03:00:00'      => '3:00 AM',
	                '04:00:00'      => '4:00 AM',
	                '05:00:00'      => '5:00 AM',
	                '06:00:00'      => '6:00 AM',
	                '07:00:00'      => '7:00 AM',
	                '08:00:00'      => '8:00 AM',
	                '09:00:00'      => '9:00 AM',
	                '10:00:00'      => '10:00 AM',
	                '11:00:00'      => '11:00 AM',
	                '12:00:00'      => '12:00 PM',
	                '13:00:00'      => '1:00 PM',
	                '14:00:00'      => '2:00 PM',
	                '15:00:00'      => '3:00 PM',
	                '16:00:00'      => '4:00 PM',
	                '17:00:00'      => '5:00 PM',
	                '18:00:00'      => '6:00 PM',
	                '19:00:00'      => '7:00 PM',
	                '20:00:00'      => '8:00 PM',
	                '21:00:00'      => '9:00 PM',
	                '22:00:00'      => '10:00 PM',
	                '23:00:00'      => '11:00 PM'
	            ),
	            'value'     => '23:00:00',
	            'label'     => Mage::helper('reports')->__('End Time'),
	            'title'     => Mage::helper('reports')->__('End Time'),
	            'required'  => true
	        ));
        }

        $fieldset->addField('show_empty_rows', 'select', array(
            'name'      => 'show_empty_rows',
            'options'   => array(
                '1' => Mage::helper('reports')->__('Yes'),
                '0' => Mage::helper('reports')->__('No')
            ),
            'label'     => Mage::helper('reports')->__('Empty Rows'),
            'title'     => Mage::helper('reports')->__('Empty Rows')
        ));

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Initialize form fileds values
     * Method will be called after prepareForm and can be used for field values initialization
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _initFormValues()
    {
        $data = $this->getFilterData()->getData();
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value[0])) {
                $data[$key] = implode(',', $value);
            }
        }
        $this->getForm()->addValues($data);
        return parent::_initFormValues();
    }

    /**
     * This method is called before rendering HTML
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _beforeToHtml()
    {
        $result = parent::_beforeToHtml();

        /** @var Varien_Data_Form_Element_Fieldset $fieldset */
        $fieldset = $this->getForm()->getElement('base_fieldset');

        if (is_object($fieldset) && $fieldset instanceof Varien_Data_Form_Element_Fieldset) {
            // apply field visibility
            foreach ($fieldset->getElements() as $field) {
                if (!$this->getFieldVisibility($field->getId())) {
                    $fieldset->removeField($field->getId());
                }
            }
            // apply field options
            foreach ($this->_fieldOptions as $fieldId => $fieldOptions) {
                $field = $fieldset->getElements()->searchById($fieldId);
                /** @var Varien_Object $field */
                if ($field) {
                    foreach ($fieldOptions as $k => $v) {
                        $field->setDataUsingMethod($k, $v);
                    }
                }
            }
        }

        return $result;
    }
}
