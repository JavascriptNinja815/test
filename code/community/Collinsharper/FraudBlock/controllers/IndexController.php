<?php

class Collinsharper_FraudBlock_IndexController extends Mage_Core_Controller_Front_Action
{

    public function cbAction()
    {
        Mage::helper('fraudblock')->setBrowserFingerPrint($this->getRequest()->getParam('fp'));
    }

}
