<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kl
 */
class Collinsharper_Pm_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function debugLog($_message, $_level = null)
    {
        Mage::log($_message, $_level, "ch_pm.log");
    }

    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    public function getCustomerId()
    {
        return Mage::registry('current_customer')->getId();
    }

    public function getDoctorsOptionHash()
    {
        $bits = array();

        $targetGroup = Mage::getModel('customer/group')->load('Doctor', 'customer_group_code');

        $doctors = Mage::getResourceModel('customer/customer_collection')
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('group_id', $targetGroup->getId())
                    ->load();

        foreach($doctors as $_doctor) {
            $bits[$_doctor->getId()] = $_doctor->getData("doctor_name");
        }

        return $bits;
    }

    public function getDoctorsOptionArray()
    {
        $bits = array();
        $bits[] = array("value" => "", "label" => "");

        $targetGroup = Mage::getModel('customer/group')->load('Doctor', 'customer_group_code');

        $doctors = Mage::getResourceModel('customer/customer_collection')
            ->addAttributeToSelect('*')
            ->addFieldToFilter('group_id', $targetGroup->getId())
            ->load();

        foreach($doctors as $_doctor) {
            $bits[] = array("value" => $_doctor->getId(), "label" => $_doctor->getData("doctor_name"));
        }

        return $bits;
    }

    public function getBackOrReturnUrlForCustomerEdit()
    {
        return Mage::getModel('adminhtml/url')->getUrl('adminhtml/customer/edit', array(
            'id'=> Mage::app()->getRequest()->getParam('customer_id'),
            'active_tab'=> 'customer_edit_tab_prescription',
        ));
    }

}