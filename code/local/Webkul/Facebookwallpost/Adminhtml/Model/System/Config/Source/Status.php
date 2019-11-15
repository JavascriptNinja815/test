<?php
class Webkul_Facebookwallpost_Adminhtml_Model_System_Config_Source_Status
{
	public function toOptionArray()
	{
		return array(
			array('value' => "samewindow", 'label'=>Mage::helper('adminhtml')->__('Same Window')),
			array('value' => "newtab", 'label'=>Mage::helper('adminhtml')->__('New Tab')),
		);
	}
}

