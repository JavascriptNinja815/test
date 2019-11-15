<?php
class ChUnitTests extends PHPUnit_Framework_TestCase
{

    var $_configValues = array();
    var $_test_xml_data  = false;
    var $_order;
    var $_invoice;
    var $_configPaths = array(
        'payment/beanprostored/active' => '1',
        'payment/beanprostored/order_status' => 'NULL',
        'payment/beanprostored/payment_action' => 'authorize_capture',
        'payment/beanpro/active' => '1',
        'payment/beanpro/test' => '1',
        'payment/beanpro/sandbox' => '1',
        'payment/beanpro/force_decline' => '0',
        'payment/beanpro/debug' => '1',
        'payment/beanpro/payment_action' => 'authorize',
        'payment/beanpro/purchase_action' => '0',
        'payment/beanpro/disable_profile_card_verification' => '0',
        'payment/beanpro/merchant_id' => '178100000',
        'payment/beanpro/login' => 'collinsharper',
        'payment/beanpro/password' => '111111111aa',
        'payment/beanpro/apikey' => 'fe400c26c9b443B8A12f9ed97a716BC0',
        'payment/beanpro/cctypes' => 'AE,VI,MC,DI,JCB',
        'payment/beanpro/max_checkout_order_total' => 'NULL',
        'payment/beanpro/cav_enabled' => '0',
        'payment/beanpro/cav_dob' => '0',
        'payment/beanpro/cav_sin' => '0',
        'payment/beanpro/cvv_approval_list' => '1,2,3,4,5,6,6',
        'payment/beanpro/avs_approval_list' => '0,5,9,A,B,C,D,E,G,I,M,N,P,R,S,U,W,X,Y,Z',
        'payment/beanpro/legato_active' => '0',
    );

    protected function setUp()
    {
        ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR
            . dirname(__FILE__) . '/../../../../../' . PATH_SEPARATOR . dirname(__FILE__));
        //Set custom memory limit
        ini_set('memory_limit', '512M');
        //Include Magento libraries
        require_once 'Mage.php';
        //Start the Magento application

        // stupid magento auto load compets with unit loads
        spl_autoload_unregister(array(Varien_Autoload::instance(), 'autoload'));
        spl_autoload_register(function($class) {
            try {
                //if(!stristr($class, 'phpunit') && !strstr($class, 'ClassLoader.php')) {
                if(!class_exists($class)) {
                    return @Varien_Autoload::instance()->autoload($class);
                }

            } catch (Exception $e) {
                if (false !== strpos($e->getMessage(), 'Warning: include(')) {
                    return null;
                } else {
                    throw $e;
                }
            }
        });


        Mage::app('default');
        //Avoid issues "Headers already send"
      //  session_start();
        $this->_payment_method = Mage::getModel('beanpro/paymentmethodstored');
        $this->_payment_method = Mage::getModel('beanpro/paymentmethod');
        $this->_config = new Mage_Core_Model_Config();
        $this->_old_config = array();
        $this->_test_xml_data =  simplexml_load_file(dirname(__FILE__) . '/unit/test_config_data.xml');

        $this->saveConfigData();
        $this->updateConfigData();
        // TODO create the products we need

       // this doenst work
        //$this->_createSimpleProduct((string)$this->_test_xml_data->product->sku);
    }

    public function _createSimpleProduct($sku)
    {
        $product = Mage::getModel('catalog/product');


        $product->loadbyAttribute('sku', $sku);
        if($product && $product->getId()) {
           return true;
        }


            $product
//    ->setStoreId(1) //you can set data in store scope
                ->setWebsiteIds(array(1)) //website ID the product is assigned to, as an array
                ->setAttributeSetId(4) //ID of a attribute set named 'default'
                ->setTypeId('simple') //product type
                ->setCreatedAt(strtotime('now')) //product creation time
//    ->setUpdatedAt(strtotime('now')) //product update time

                ->setSku($sku) //SKU
                ->setName('test product21') //product name
                ->setWeight(4.0000)
                ->setStatus(1) //product status (1 - enabled, 2 - disabled)
                ->setTaxClassId(4) //tax class (0 - none, 1 - default, 2 - taxable, 4 - shipping)
                ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH) //catalog and search visibility
                ->setManufacturer(28) //manufacturer id
                ->setColor(24)
                ->setNewsFromDate('06/26/2014') //product set as new from
                ->setNewsToDate('06/30/2014') //product set as new to
                ->setCountryOfManufacture('AF') //country of manufacture (2-letter country code)

                ->setPrice(2.00) //price in form 11.22
                ->setCost(22.33) //price in form 11.22
             //   ->setSpecialPrice(2.00) //special price in form 11.22
             //   ->setSpecialFromDate('06/1/2014') //special price from (MM-DD-YYYY)
             //   ->setSpecialToDate('06/30/2014') //special price to (MM-DD-YYYY)
               // ->setMsrpEnabled(1) //enable MAP
            //    ->setMsrpDisplayActualPriceType(1) //display actual price (1 - on gesture, 2 - in cart, 3 - before order confirmation, 4 - use config)
           //     ->setMsrp(99.99) //Manufacturer's Suggested Retail Price

                ->setMetaTitle('test meta title 2')
                ->setMetaKeyword('test meta keyword 2')
                ->setMetaDescription('test meta description 2')

                ->setDescription('This is a long description')
                ->setShortDescription('This is a short description')

               // ->setMediaGallery (array('images'=>array (), 'values'=>array ())) //media gallery initialization
              //  ->addImageToMediaGallery('media/catalog/product/1/0/10243-1.png', array('image','thumbnail','small_image'), false, false) //assigning image, thumb and small image to media gallery

                ->setStockData(array(
                        'use_config_manage_stock' => 0, //'Use config settings' checkbox
                        'manage_stock'=>1, //manage stock
                        'min_sale_qty'=>1, //Minimum Qty Allowed in Shopping Cart
                        'max_sale_qty'=>2, //Maximum Qty Allowed in Shopping Cart
                        'is_in_stock' => 1, //Stock Availability
                        'qty' => 999 //qty
                    )
                )

                ->setCategoryIds(array(3, 10)); //assign product to categories
            $product->save();
    }

    public function _buildOrder($xml, $_productXML)
    {
        $root_xml = $this->_test_xml_data;

        $_customerXML = $root_xml->customer;


        $storeId         = (String) $root_xml->store_id;
        $reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);
        $order           = Mage::getModel('sales/order')
            ->setIncrementId($reservedOrderId)
            ->setStoreId($storeId)
            ->setQuoteId(0)
            ->setGlobal_currency_code((String) $root_xml->currency)
            ->setBase_currency_code((String) $root_xml->currency)
            ->setStore_currency_code((String) $root_xml->currency)
            ->setOrder_currency_code((String) $root_xml->currency);

        // set Customer data
        $order->setCustomer_email((String) $_customerXML->email)
            ->setCustomerFirstname((String) $_customerXML->firstname)
            ->setCustomerLastname((String) $_customerXML->lastname)
            ->setCustomerGroupId(1)
            ->setCustomer_is_guest(1);

        // set Billing Address
        $billingAddress = Mage::getModel('sales/order_address')
            ->setStoreId($storeId)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
            ->setFirstname((String) $_customerXML->billing->firstname)
            ->setLastname((String) $_customerXML->billing->lastname)
            ->setStreet((String) $_customerXML->billing->street)
            ->setCity((String) $_customerXML->billing->city)
            ->setCountry_id((String) $_customerXML->billing->country_id)
            ->setRegion((String) $_customerXML->billing->region)
            ->setPostcode((String) $_customerXML->billing->postcode)
            ->setTelephone((String) $_customerXML->billing->telephone);

        $order->setBillingAddress($billingAddress);

        $shippingAddress = Mage::getModel('sales/order_address')
            ->setStoreId($storeId)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
            ->setFirstname((String) $_customerXML->shipping->firstname)
            ->setLastname((String) $_customerXML->shipping->lastname)
            ->setStreet((String) $_customerXML->shipping->street)
            ->setCity((String) $_customerXML->shipping->city)
            ->setCountry_id((String) $_customerXML->shipping->country_id)
            ->setRegion((String) $_customerXML->shipping->region)
            ->setPostcode((String) $_customerXML->shipping->postcode)
            ->setTelephone((String) $_customerXML->shipping->telephone);

        $order->setShippingAddress($shippingAddress)
            ->setShipping_method('flatrate_flatrate')
            ->setShippingDescription('flatrate');

        $orderPayment = Mage::getModel('sales/order_payment')
            ->setStoreId($storeId)
            ->setCustomerPaymentId(11)
            ->setMethod((string)$this->_test_xml_data->method->code);

        $orderPayment->setCcType((String) $xml->card->type)
            ->setCcOwner(null)
            ->setCcLast4((String) $xml->card->cc_last4)
            ->setCcNumber((String) $xml->card->cc_num)
            ->setCcCid($xml->card->cvv)
            ->setCcExpMonth($xml->card->cc_exp_m)
            ->setCcExpYear($xml->card->cc_exp_y)
            ->setCcSsIssue(null)
            ->setCcSsStartMonth(null)
            ->setCcSsStartYear(null);

        $order->setPayment($orderPayment);

        // We will only have 1 product all the time
        $subTotal = 0;
        //Products
        $product_sku = (int) $_productXML->sku;

        if ($_productXML->is_rand == "true") {
            $products = Mage::getModel('catalog/product')->getCollection();
            $products->addFieldToFilter("type_id", "simple");
            $prod_ids = array();
            foreach ($products as $product) {
                array_push($prod_ids, $product->getId());
            }
            $rand       = rand(0, count($prod_ids));
            $product_id = $prod_ids[$rand];
        }

        if(!isset($product_id)) {
            $product = Mage::getModel('catalog/product');


            $product->loadbyAttribute('sku', $product_sku);
            $product_id = $product->getId();

        }




        // TODO fix
        $_product = Mage::getModel('catalog/product')->load(2);
        $price    = $_product->getFinalPrice();

        // If we had XML Price per record?
        if (isset($xml->product->price) && (int)$xml->product->price > 0) {
            $price    = (double) $xml->product->price;

            if ($price < 0) {
                $price = $_product->getPrice();
            }

        }


        $rowTotal  = $price * (int) $xml->product->qty;
        $orderItem = Mage::getModel('sales/order_item')
            ->setStoreId($storeId)
            ->setQuoteItemId(0)
            ->setQuoteParentItemId(null)
            ->setProductId($product_id)
            ->setProductType($_product->getTypeId())
            ->setQtyBackordered(null)
            ->setTotalQtyOrdered((int) $xml->product->qty)
            ->setQtyOrdered((int) $xml->product->qty)
            ->setName($_product->getName())
            ->setSku($_product->getSku())
            ->setPrice($price)
            ->setBasePrice($price)
            ->setOriginalPrice($price)
            ->setRowTotal($rowTotal)
            ->setBaseRowTotal($rowTotal);

        $subTotal += $rowTotal;
        $order->addItem($orderItem);


        $order->setSubtotal($subTotal)
            ->setBaseSubtotal($subTotal)
            ->setGrandTotal($subTotal)
            ->setBaseGrandTotal($subTotal);

        return $order;
    }

    public function testSimpleOrderAuthorizeSingleItemFull()
    {
        $this->_testSimpleOrderAuthorizeSingle();
        $this->_testSimpleOrderInvoiceAll();

        // TODO: some gateways need a delay
        // sleep(60*60*3)
        $this->_testSimpleOrderCreditMemo();
    }


    public function testSimpleOrderAuthorizeTwoItemFull()
    {
        $this->_testSimpleOrderAuthorizeTwo();
        $this->_testSimpleOrderInvoiceAll();

        // TODO: some gateways need a delay
        // sleep(60*60*3)
        $this->_testSimpleOrderCreditMemo(1);
        $this->_testSimpleOrderCreditMemo(1);
        $this->_order = mage::getModel('sales/order')->load($this->_order->getId());
        $this->assertEquals(2, count($this->_order->getCreditmemosCollection()));
    }

    public function testSimpleOrderAuthorizeTwoItemInvoice()
    {
        $this->_testSimpleOrderAuthorizeTwo();
        $this->_testSimpleOrderInvoiceAll(1);
        $this->_testSimpleOrderCreditMemo(1);

        $this->_testSimpleOrderInvoiceAll(1);
        $this->_testSimpleOrderCreditMemo(1);

        // TODO: some gateways need a delay
        // sleep(60*60*3)
        $this->_order = mage::getModel('sales/order')->load($this->_order->getId());
        $this->assertEquals(2, count($this->_order->getCreditmemosCollection()));
    }

    public function testSimpleOrderCaptureTwoItemInvoice()
    {
        /*
         *        'payment/beanprostored/payment_action' => 'authorize_capture',
        'payment/beanpro/payment_action' => 'authorize',
         */
        $this->_config->saveConfig('payment/beanpro/payment_action' , 'authorize_capture', 'default', 0);
        $this->_testSimpleOrderAuthorizeTwo(true);
        if(!$this->_invoice) {
            $this->_order = Mage::getModel('sales/order')->load($this->_order->getId());
            foreach($this->_order->getInvoiceCollection() as $invoice) {
                $this->_invoice  = $invoice;
                break;
            }
        }

        $this->_testSimpleOrderCreditMemo(1);

        $this->_testSimpleOrderCreditMemo(1);

        // TODO: some gateways need a delay
        // sleep(60*60*3)
        $this->_order = mage::getModel('sales/order')->load($this->_order->getId());
        $this->assertEquals(2, count($this->_order->getCreditmemosCollection()));
        $this->assertEquals(1, count($this->_order->getInvoiceCollection()));
    }

    public function _createInvoice($savedQtys)
    {
        $order = $this->_order;
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($savedQtys);

        if (!$invoice->getTotalQty()) {
            Mage::throwException($this->__('Cannot create an invoice without products.'));
        }

        $invoice->setRequestedCaptureCase(true);

        $invoice->register();
        $invoice->getOrder()->setCustomerNoteNotify(0);
        $invoice->getOrder()->setIsInProcess(true);


        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

        $transactionSave->save();
        $this->assertNotEmpty($invoice->getIncrementId());
        $this->_invoice = $invoice;
    }

    public function _testSimpleOrderInvoiceAll($forceQty = false)
    {
        $order = $this->_order;

        if (!$order->canInvoice()) {
            // TODO: force error message from invoice assertion
            $this->assertsTrue(false);
            echo " cannot invoice";
            return false;
        }

        $savedQtys = array();
        foreach($order->getAllItems() as $item) {
            $savedQtys[$item->getId()] = (int)$item->getData('qty_ordered');
            if($forceQty) {
                $savedQtys[$item->getId()] = $forceQty;
            }
        }
        $this->_createInvoice($savedQtys);
    }

    function _log($x)
    {
        //echo $x."\n";
    }

    public function _testSimpleOrderAuthorizeSingle()
    {
        $this->_log(__FUNCTION__  );
        $orderWasCreated = false;
        $order = $this->_buildOrder($this->_test_xml_data->tests->simple_test,
            $this->_test_xml_data->product);

        try
        {
            $order->getPayment()->authorize($order->getPayment(), $order->getGrandTotal());
            $order->save();
            $orderWasCreated = true;
        } catch (Exception $e) {
            echo " failure in " . __FUNCTION__ . " - " . $e->getMessage() . "\n";
        }

        $this->assertTrue($orderWasCreated);
        $this->assertNotEmpty($order->getIncrementId());
        $this->_order = $order;

        // invoice the order.

    }

    public function _testSimpleOrderCreditMemo($forceQty = false)
    {
        $invoice = $this->_invoice;
        $order = $this->_order;
        $invoice->setOrder($order);

        if (!$order->canCreditmemo()) {
            echo " cant inovoice";
            $this->assertsTrue(false);
        }

// Array ( [items] => Array ( [358] => Array ( [qty] => 1 ) ) [do_offline] => 0 [comment_text] => [shipping_amount] => 0 [adjustment_positive] => 0 [adjustment_negative] => 0 [qtys] => Array ( [358] => 1 ) )
        $data = array(
            'do_offline' => 0,
            'comment_text' => '',
            'shipping_amount' => 0,
            'adjustment_positive' => 0,
            'adjustment_negative' => 0,
        );

        $backToStock = array();
        $data['qtys']  = array();
        foreach($order->getAllItems() as $item) {
            $qty =   (int)$item->getData('qty_ordered');
            if($forceQty ) {
                $qty = $forceQty ;
            }

            $data['items'][$item->getId()] = array('qty' => $qty);
            $data['qtys'][$item->getId()] = (int)$qty;
            $backToStock[$item->getId()] = true;
        }


        $service = Mage::getModel('sales/service_order', $order);


        $creditmemo = $service->prepareInvoiceCreditmemo($invoice, $data);

        Mage::register('current_creditmemo', $creditmemo);

        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            $orderItem = $creditmemoItem->getOrderItem();
            $parentId = $orderItem->getParentItemId();
            if (isset($backToStock[$orderItem->getId()])) {
                $creditmemoItem->setBackToStock(true);
            } elseif ($orderItem->getParentItem() && isset($backToStock[$parentId]) && $backToStock[$parentId]) {
                $creditmemoItem->setBackToStock(true);
            } elseif (empty($savedData)) {
                $creditmemoItem->setBackToStock(Mage::helper('cataloginventory')->isAutoReturnEnabled());
            } else {
                $creditmemoItem->setBackToStock(false);
            }
        }

        if (($creditmemo->getGrandTotal() <=0) && (!$creditmemo->getAllowZeroGrandTotal())) {
            Mage::throwException(
                ('Credit memo\'s total must be positive.')
            );
        }

        $creditmemo->setRefundRequested(true);
        $creditmemo->setOfflineRequested(false);

        $creditmemo->register();


        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($creditmemo)
            ->addObject($creditmemo->getOrder());
        if ($creditmemo->getInvoice()) {
            $transactionSave->addObject($creditmemo->getInvoice());
        }
        $transactionSave->save();

        $this->assertNotEmpty($creditmemo->getIncrementId());
        Mage::unregister('current_creditmemo');

    }

    public function _testSimpleOrderAuthorizeTwo($isCapture = false)
    {
        $this->_log(__FUNCTION__  );
        $this->_order = false;
        $this->_invoice = false;

        $orderWasCreated = false;
        $order = $this->_buildOrder($this->_test_xml_data->tests->simple_test_two,
            $this->_test_xml_data->product);

        try
        {
            if($isCapture) {
               $order->getPayment()->capture(null);
//                $ret = $this->_payment_method->capture($order->getPayment(), $order->getGrandTotal());
            } else {
                $order->getPayment()->authorize($order->getPayment(), $order->getGrandTotal());
            }

            $order->save();
            $orderWasCreated = true;
        } catch (Exception $e) {
            echo " failure in " . __FUNCTION__ . " - " . $e->getMessage() . "\n";
        }

        $this->assertTrue($orderWasCreated);
        $this->assertNotEmpty($order->getIncrementId());
        $this->_order = $order;
        // invoice the order.

    }


    // TODO : this wont work as the recurring crap is handle by checkout or somethign else..
    public function __testRecurringOrder()
    {
        $win = false;
        $order = $this->_buildOrder($this->_test_xml_data->tests->recurring_test,
            $this->_test_xml_data->product_recurring);


        try
        {
            $order->getPayment()->authorize($order->setPayment, $order->getGrandTotal());
            $order->save();
            $win = true;
        } catch (Exception $e) {
            echo " failure in " . __FUNCTION__ . " - " . $e->getMessage() . "\n";
        }

        $this->assertTrue($win);
        $this->assertNotEmpty($order->getIncrementId());
    }

    protected function saveConfigData($storeId = false)
    {
        $this->_configValues = array();
        // TODO : handle store level config
        foreach($this->_configPaths as $path => $value) {
            $this->_configValues[$path] = Mage::getStoreConfig($path);
        }
    }

    protected function revertConfigData()
    {
        foreach($this->_configValues as $path => $value) {
            $this->_config->saveConfig($path, $value, 'default', 0);
        }
    }

    protected function updateConfigData()
    {
        foreach($this->_configPaths as $path => $value) {
            $this->_config->saveConfig($path, $value, 'default', 0);
        }
    }

    protected function tearDown()
    {
        // put config bach
        $this->revertConfigData();

    }
}

