<?php

class Collinsharper_Chcategoryforms_Helper_Order extends Mage_Core_Helper_Abstract
{


    private $_storeId = '1';
    private $_groupId = '1';
    private $shipping_method = 'flatrate_flatrate';
    private $_sendConfirmation = '0';
    private $orderData = array();
    private $_sourceCustomer;
    const STEREO_ORDER = 'stereo';
    const B2B_ESTIMATE = 'b2bestimate';
    const B2B_ORDER = 'b2border';

    public function getOrderData($order)
    {
        $order_data = null;
        $org_order_info = $order->getData( 'post_data' );
        if ( $org_order_info != null ) {
            $order_data = unserialize( $org_order_info );
        }
        return $order_data;
    }

    public function isStereoOrder($order)
    {
        return $this->getOrderType($order) == self::STEREO_ORDER;
    }

    public function isBtbEstimate($order)
    {
        return $this->getOrderType($order) == self::B2B_ESTIMATE;
    }

    public function isBtbOrder($order)
    {
        return $this->getOrderType($order) == self::B2B_ORDER;
    }

    public function getOrderType($order)
    {
        $orderType = false;
        $order_data = $this->getOrderData($order);

        mage::log(__METHOD__ . __LINE__ . " we have " . print_r($order_data,1));

        if ( $order_data != null && is_array($order_data)) {
            if ( isset( $order_data['tag_stereo_order'] ) ) {
                $orderType = self::STEREO_ORDER;
            } else if ( isset( $order_data['tag_b2b_order'] ) ) {
                $orderType = self::B2B_ORDER;
            } else if ( isset( $order_data['tag_b2b_estimate'] ) ) {
                $orderType = self::B2B_ESTIMATE;
            }
        }

        return $orderType;
    }

    public function createOrderFromQuote($quoteId) {
        $quote = Mage::getModel('sales/order_quote')->load($quoteId);

        $personsOrder = Mage::getModel('sales/order')->load($scheduleorder->getOrderId());
        //$personsOrder->setReordered(true);
        $items = $quote->getAllItems();
        foreach($items as $item) {
            $products[$item->getProductId()] = array('qty' => $item->getQtyOrdered());
        }
        
        $this->shipping_method = 'flatrate_flatrate';
        $this->_sourceCustomer = $customer = Mage::getModel('customer/customer')->load($personsOrder->getCustomerId());
        $this->setOrderInfo($products, $quote);
        $this->create();
        $personsOrder->setPaymentMethod('checkmo');

        $order_model = Mage::getSingleton('adminhtml/sales_order_create');
        /*$order_model->setPaymentData('cashondelivery');
        $order_model->getQuote()->getPayment()->addData('cashondelivery');
        $order_model->setShipping('flatrate_flatrate');
        $order_model->getQuote()->getShipping()->addData('flatrate_flatrate');
        $order_model->getQuote()->setShipping(array('method' => 'flatrate_flatrate'));
        $order_model->getQuote()->setPayment(array('method' => 'cashondelivery'));

        $order_model->initFromOrder($personsOrder);
        $order_model->createOrder();*/
    }






    public function setOrderInfo($products, $quote)
    {
        //You can extract/refactor this if you have more than one product, etc.
        $Billingaddress = Mage::getModel('customer/address')->load($this->_sourceCustomer->getDefaultBilling());
        $Shippingaddress = Mage::getModel('customer/address')->load($this->_sourceCustomer->getDefaultShipping());
        $this->orderData = array(
            'session'       => array(
                'customer_id'   => $this->_sourceCustomer->getId(),
                'store_id'      => $quote->getStore()->getId(),
            ),
            'payment'       => array(
                'method'    => 'checkmo',
            ),
            'add_products'  =>$products,
            'order' => array(
                'currency' => 'USD',
                'account' => array(
                    'group_id' => $this->_groupId,
                    'email' => $this->_sourceCustomer->getEmail()
                ),
                'billing_address' => array(
                    'customer_address_id' => $this->_sourceCustomer->getDefaultBilling(),
                    'prefix' => '',
                    'firstname' => $this->_sourceCustomer->getFirstname(),
                    'middlename' => '',
                    'lastname' => $this->_sourceCustomer->getLastname(),
                    'suffix' => '',
                    'company' => '',
                    'street' => array($Billingaddress->getStreet(),''),
                    'city' => $Billingaddress->getCity(),
                    'country_id' => $Billingaddress->getCountryId(),
                    'region' => '',
                    'region_id' => $Billingaddress->getRegionId(),
                    'postcode' => $Billingaddress->getPostcode(),
                    'telephone' => $Billingaddress->getTelephone(),
                    'fax' => '',
                ),
                'shipping_address' => array(
                    'customer_address_id' => $this->_sourceCustomer->getDefaultShipping(),
                    'prefix' => '',
                    'firstname' => $this->_sourceCustomer->getFirstname(),
                    'middlename' => '',
                    'lastname' => $this->_sourceCustomer->getLastname(),
                    'suffix' => '',
                    'company' => '',
                    'street' => array($Shippingaddress->getStreet(),''),
                    'city' => $Shippingaddress->getCity(),
                    'country_id' => $Shippingaddress->getCountryId(),
                    'region' => '',
                    'region_id' => $Shippingaddress->getRegionId(),
                    'postcode' => $Shippingaddress->getPostcode(),
                    'telephone' => $Shippingaddress->getTelephone(),
                    'fax' => '',
                ),
                'shipping_method' => 'flatrate_flatrate',
                'comment' => array(
                    'customer_note' => 'This order has been created by scheduler order script.',
                ),
                'send_confirmation' => false
            ),
        );
    }

    protected function _getOrderCreateModel()
    {
        return Mage::getSingleton('adminhtml/sales_order_create');
    }
    /**
     * Retrieve session object
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }
    /**
     * Initialize order creation session data
     *
     * @param array $data
     * @return Mage_Adminhtml_Sales_Order_CreateController
     */
    protected function _initSession($data)
    {
        /* Get/identify customer */
        if (!empty($data['customer_id'])) {
            $this->_getSession()->setCustomerId((int) $data['customer_id']);
        }
        /* Get/identify store */
        if (!empty($data['store_id'])) {
            $this->_getSession()->setStoreId((int) $data['store_id']);
        }
        return $this;
    }

    public function create(){
        $orderData = $this->orderData;

        if (!empty($orderData)) {
            $this->_initSession($orderData['session']);
            try {
                $this->_processQuote($orderData);
                if (!empty($orderData['payment'])) {
                    $this->_getOrderCreateModel()->setPaymentData($orderData['payment']);
                    $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($orderData['payment']);
                }

                Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, "0");
                $_order = $this->_getOrderCreateModel()->importPostData($orderData['order'])->createOrder();

                return $_order;
            }
            catch (Exception $e){
                Mage::log("Order save error...");
            }
        }
        return null;
    }

    protected function _processQuote($data = array()){
        /* Saving order data */
        if (!empty($data['order'])) {
            $this->_getOrderCreateModel()->importPostData($data['order']);
        }
        $this->_getOrderCreateModel()->getBillingAddress();
        $this->_getOrderCreateModel()->setShippingAsBilling(true);
        /* Just like adding products from Magento admin grid */
        if (!empty($data['add_products'])) {
            $this->_getOrderCreateModel()->addProducts($data['add_products']);
        }
        /* Collect shipping rates */
        $this->_getOrderCreateModel()->collectShippingRates();
        /* Add payment data */
        if (!empty($data['payment'])) {
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($data['payment']);
        }
        $this->_getOrderCreateModel()->initRuleData()->saveQuote();

        if (!empty($data['payment'])) {
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($data['payment']);
        }
        return $this;
    }
}