<?php
class Collinsharper_Upsaddressvalidation_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
      
	  $this->loadLayout();   
	  $this->getLayout()->getBlock("head")->setTitle($this->__("Some Page"));
	        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
      $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home Page"),
                "title" => $this->__("Home Page"),
                "link"  => Mage::getBaseUrl()
		   ));

      $breadcrumbs->addCrumb("some page", array(
                "label" => $this->__("Some Page"),
                "title" => $this->__("Some Page")
		   ));

      $this->renderLayout(); 
	  
    }
public function errorAction()
{
Mage::throwException('Please skin this page!');

}


}
