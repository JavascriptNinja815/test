<?php

class Collinsharper_FraudBlock_Model_Observer
{

    protected function getRequest()
    {
        return Mage::app()->getRequest();
    }

    protected function isMaskCustomerPassword()
    {
        return Mage::getStoreConfig('sales/fraud_block/password_fix') == true;
    }

    protected function help()
    {
        return Mage::helper('fraudblock');
    }

    protected function isEnabled()
    {
        return Mage::getStoreConfig('sales/fraud_block/enabled') == true;
    }

    protected function log($x, $force = false)
    {
        $this->help()->log($x, $force);
    }

    protected function getActionName()
    {
        return Mage::app()->getRequest()->getActionName();
    }

    protected function isCheckout()
    {
        return Mage::app()->getRequest()->getRouteName() == 'checkout';
    }

    protected function isCheckoutFailure()
    {
        return $this->isCheckout() && $this->getActionName() == 'failure';
    }

    protected function getCmsUrl()
    {
        $url = Mage::getUrl(Mage::getStoreConfig('sales/fraud_block/cms_page'));
        return $url;
    }

    protected function isCheckoutSaveOrder()
    {
        return $this->isCheckout() && $this->getActionName() == 'saveOrder';
    }


    public function controllerActionPreDispatch(Varien_Event_Observer $observer)
    {
        if(!$this->isEnabled()) {
            return;
        }

        if($this->isCheckout() && Mage::helper('fraudblock')->isFraud()) {
            $this->log(__METHOD__ . __LINE__ . " is checkout and is fraud REDIRECT", true);
            if($this->isCheckoutSaveOrder()) {
                // make json to recirect
                $x = array('redirect' => $this->getCmsUrl());
                echo json_encode($x);
            } else {
                header('location: ' . $this->getCmsUrl());
            }
            exit;
        }

        if($this->isCheckoutFailure()) {
            $this->log(__METHOD__ . __LINE__ . " is checkout failure ");
            Mage::helper('fraudblock')->recordFraudAttempt();
        }

    }

    public function processOrderPlace(Varien_Event_Observer $observer)
    {
        $this->log(__METHOD__ . __LINE__ );
        if (!$this->isEnabled()) {
            return $this;
        }

        if(Mage::helper('fraudblock')->isFraud()) {
            throw new Exception("We are unable to process the order. Please contact customer Support 003");
        }

        //$order = $observer->getEvent()->getOrder();

        return $this;
    }


    public function processOrderFail(Varien_Event_Observer $observer)
    {
        $this->log(__METHOD__ . __LINE__ );
        if (!$this->isEnabled()) {
            return $this;
        }

        $order = $observer->getEvent()->getOrder();
        $isFraud = false;
        $fraudRule = false;
        //$this->log(__METHOD__ . __LINE__ . " " . $observer->getEvent()->getControllerAction()->getFullActionName());

        // we have to record the failure here if its paymetn submit
        Mage::helper('fraudblock')->recordFraudAttempt();

        $this->log(__METHOD__ . __LINE__ . " not a success ");
        $fraudRule = Mage::helper('fraudblock')->getFraudRule();
        $isFraud = Mage::helper('fraudblock')->isFraud($fraudRule);



        if($isFraud || Mage::getStoreConfig('sales/fraud_block/log_all')) {
            // record  checkout failures or record all if they want to.
            Mage::helper('fraudblock')->recordFraudBlockHit($fraudRule, true);

        }

        if ($isFraud) {
            $this->log(__METHOD__ . __LINE__ . " forcing redirect fail ");
            throw new Exception("We are unable to process the order. Please contact customer Support 006");
        }


        return $this;
    }


    public function customerRegisterSuccess($observer)
    {
        if(!$this->isMaskCustomerPassword()) {
            return $this;
        }
        $customer = $observer->getCustomer();
        Mage::getSingleton('customer/session')->setTempPassword($customer->getPassword());
        $customer->setPassword($this->help()->__('XXXXXXXXX (password masked for security)'));
    }

    public function customerRegisterSuccessPostDispatch($observer)
    {
        if(!$this->isMaskCustomerPassword()) {
            Mage::getSingleton('customer/session')->unsTempPassword();
            return $this;
        }
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        if($customer && Mage::getSingleton('customer/session')->getTempPassword()) {
            $customer->changePassword(Mage::getSingleton('customer/session')->getTempPassword());
        }
        Mage::getSingleton('customer/session')->unsTempPassword();
    }

    public function __old__controllerActionPostDispatch($observer)
    {

        if(!$this->isEnabled()) {
            return $this;
        }

        $isFraud = false;
        $fraudRule = false;
        //$this->log(__METHOD__ . __LINE__ . " " . $observer->getEvent()->getControllerAction()->getFullActionName());
        if($this->isCheckoutSaveOrder()) {
            //   $this->log(__METHOD__ . __LINE__ . " save order " );
            $result = json_decode(Mage::app()->getResponse()->getBody());

            if (!$result->success) {
                // we have to record the failure here if its paymetn submit
                Mage::helper('fraudblock')->recordFraudAttempt();

                $this->log(__METHOD__ . __LINE__ . " not a success ");
                $fraudRule = Mage::helper('fraudblock')->getFraudRule();
                $isFraud = Mage::helper('fraudblock')->isFraud($fraudRule);

                if ($isFraud) {
                    $this->log(__METHOD__ . __LINE__ . " forcing redirect fail ");
                    $result->redirect = $this->getCmsUrl();
                    Mage::app()->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                }
            }
            if($isFraud || Mage::getStoreConfig('sales/fraud_block/log_all')) {
                // record  checkout failures or record all if they want to.
                Mage::helper('fraudblock')->recordFraudBlockHit($fraudRule, !$result->success);

            }
        }
    }

    public function controllerActionPostDispatch($observer)
    {

        if(!$this->isEnabled()) {
            return $this;
        }
        // TODO watch for giftcard_code in the request; - mark it
        // in the post dispatch check the response, was it invalid/ if so mark the tick and count them.
    }
}
