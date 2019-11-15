<?php

class Collinsharper_Contacts_Model_Shipment extends Mage_Sales_Model_Order_Shipment {
#class Collinsharper_Contacts_Model_Shipment extends Collinsharper_Custom_Model_Order_Shipment {

    private $allowEmails = false;

    public function __construct() {

        parent::__construct();

    }


    public function sendEmail($notifyCustomer = true, $comment = '')
    {

        mage::log(__METHOD__ . __LINE__ , null, 'ch_Shippingmess.log');

        $order = $this->getOrder();
        $shipping_amount = $order->getShippingAmount();

        if ($shipping_amount == 0) {
            define("CH_SEND_EMAIL",true);
        } else {
            define("CH_SEND_EMAIL",false);
        }

        if (!CH_SEND_EMAIL &&  !$this->allowEmails) {
            Mage::getSingleton('adminhtml/session')->addError('Emails will be sent on schedule as per liz.');

            return $this;
        }

        mage::log(__METHOD__ . __LINE__ , null, 'ch_Shippingmess.log');
        return parent::sendEmail(true, $comment);

    }


    public function setAllowEmails($status) {

        $this->allowEmails = $status;

    }

}
