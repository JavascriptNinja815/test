<?php


class Collinsharper_Beanstreamprofiles_Model_Observer
{
	public function license($observer)
	{
		Mage::helper('beanstreamprofiles')->validLicense();
		return $this;
	}
}