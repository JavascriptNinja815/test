<?php

class Collinsharper_Inquiry_IndexController extends Mage_Core_Controller_Front_Action
{

    public function cbAction()
    {
        Mage::helper('chinquiry')->setBrowserFingerPrint($this->getRequest()->getParam('fp'));
    }

}
