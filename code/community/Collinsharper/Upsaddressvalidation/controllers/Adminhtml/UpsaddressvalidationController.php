<?php
class Collinsharper_Upsaddressvalidation_Adminhtml_UpsaddressvalidationController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
    {
       $this->loadLayout();
	   $this->_title($this->__("Backend Page Title"));
	   $this->renderLayout();
    }
}