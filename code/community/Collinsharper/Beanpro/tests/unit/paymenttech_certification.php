<?php
error_reporting(-1 ); ini_set("display_startup_errors","1"); 
ini_set("display_errors","1");
date_default_timezone_set('America/Los_Angeles');
/**
 * Collinsharper Backend Scripts
 *
 * PHP version 5
 *
 * @category Backend_Scripts
 * @package  Collinsharper.Backend.Scripts
 * @author   Collins Harper  <ch@collinsharper.com>
 * @license  http://collinsharper.com Proprietary License
 * @link     http://collinsharper.com
 */

if (file_exists('config.xml')) {
    $root_xml = simplexml_load_file('config.xml');
} else {
    exit('Failed to open config.xml.');
}


require_once '../app/Mage.php';
Mage::app();

try {

    // confgure payment method. here.
    //TODO: setup code so that we can switch between auth and purchase modes
    $cfg_backup = array();
    $parts = array('test', 'debug', 'merchant_id', 'terminal_id', 'bin', 'login', 'password', 'wsdl', 'gateway');
    $code = (string) $root_xml->merchant->code;

    $cfg = new Mage_Core_Model_Config();

    foreach($parts as $p) {
        $cpath = "payment/{$code}/{$p}";
        $v = Mage::getStoreConfig($cpath);
        $nv = (string) $root_xml->merchant->{$p};
        $cfg_backup[$p] = $v;
        //$cfg->saveConfig($cpath, "1");
    }



    // Prepare Common Data
    $_customerXML = $root_xml->customer;
    $_productXML = $root_xml->product;

    // Prepare Output Data
    $_Output = array();

    foreach($root_xml->tests->test as $xml)
    {
        try {


        // Prepare Output Data per Test
        $_Output[(string) $xml->test_id]  = array(
            "order_id" => "",
            "invoice_id" => "",
            "request_id" => "",
            "request_card" => "",
            "avs_zip" => "",
            "cvd" => "",
            "amount" => "",
            "auth_code" => "",
            "resp_code" => "",
            "asv__code" => "",
            "cvv__code" => "",
            "txRefNum" => "",
            "org_request" => "",
        );

        $_Output[(string) $xml->test_id]["request_id"] = $xml->test_id;
        $_Output[(string) $xml->test_id]["request_card"] = $xml->test_title;
        $_Output[(string) $xml->test_id]["org_request"] = $xml;

        echo "Working on ".$_Output[(string) $xml->test_id]["request_card"]."\r\n";

        // Depends on the action
        $_transactionType = "unknown";

        if (isset($xml->payment_actions->authorize))
        {
            $_transactionType = "authorize";
        } else {
            if (isset($xml->payment_actions->capture))
            {
                $_transactionType = "capture";
            } else {
                if (isset($xml->payment_actions->void))
                {
                    $_transactionType = "void";
                }
            }
        }

        switch ($_transactionType)
        {
            /******************************************************************************************************/
            /* VOID
            /******************************************************************************************************/
            case "void":
            {
                // We are not doing any void transaction
                $_reference_transaction_xml = $_Output[(string) $xml->payment_actions->ref]["org_request"];
                $_Output[(string) $xml->test_id]["amount"] = $_reference_transaction_xml->product->price;

                $_Output[(string) $xml->test_id]["txRefNum"] = "authorization void not supported";
            }
            break;
            /******************************************************************************************************/
            /* AUTHORIZATION
            /******************************************************************************************************/
            case "authorize":
            {
                // We are not doing any void transaction
                if ((double) $xml->product->price == 0.00)
                {
                    $_Output[(string) $xml->test_id]["avs_zip"] = $xml->billing->postcode;
                    $_Output[(string) $xml->test_id]["amount"] = $xml->product->price;

                    $_Output[(string) $xml->test_id]["txRefNum"] = "$0.00 dollar authorization not supported";
                } else {
                    // Do the actual Authorization
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

                    // If we had XML Billing Postal Code per record?
                    if (isset($xml->billing->postcode))
                    {
                        $billingAddress->setPostcode((String) $xml->billing->postcode);
                    }

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

                    // If we had XML Shipping Postal Code per record?
                    if (isset($xml->shipping->postcode))
                    {
                        $shippingAddress->setPostcode((String) $xml->shipping->postcode);
                    }

                    $order->setShippingAddress($shippingAddress)
                        ->setShipping_method('flatrate_flatrate')
                        ->setShippingDescription('flatrate');

                    $orderPayment = Mage::getModel('sales/order_payment')
                        ->setStoreId($storeId)
                        ->setCustomerPaymentId(11)
                        ->setMethod("chpaymentech");

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
                    $product_id = (int) $_productXML->id;

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

                    $_product = Mage::getModel('catalog/product')->load($product_id);
                    $price    = $_productXML->price;

                    // If we had XML Price per record?
                    if (isset($xml->product->price))
                    {
                        $price    = (double) $xml->product->price;
                    }
                    if ($price < 0) {
                        $price = $_product->getPrice();
                    }
                    $rowTotal  = $price * (int) $_productXML->qty;
                    $orderItem = Mage::getModel('sales/order_item')
                        ->setStoreId($storeId)
                        ->setQuoteItemId(0)
                        ->setQuoteParentItemId(null)
                        ->setProductId($product_id)
                        ->setProductType($_product->getTypeId())
                        ->setQtyBackordered(null)
                        ->setTotalQtyOrdered((int) $_productXML->qty)
                        ->setQtyOrdered((int) $_productXML->qty)
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

                    try
                    {
                        $order->getPayment()->authorize($orderPayment, $price);
                        $order->save();
                    } catch (Exception $error)
                    {

                    }


                    // Save the result
                    $_returnXML = $order->getPayment()->getMethodInstance()->_soapResponse->SOAPBody->NewOrderResponse->return;
                    $_Output[(string) $xml->test_id]["avs_zip"] = $billingAddress->getPostcode();
                    $_Output[(string) $xml->test_id]["cvd"] = $xml->card->cvv;
                    $_Output[(string) $xml->test_id]["amount"] = $price;
                    $_Output[(string) $xml->test_id]["auth_code"] = $_returnXML->authorizationCode;
                    $_Output[(string) $xml->test_id]["resp_code"] = $_returnXML->respCode;
                    $_Output[(string) $xml->test_id]["asv__code"] = $_returnXML->avsRespCode;
                    $_Output[(string) $xml->test_id]["cvv__code"] = $_returnXML->cvvRespCode;
                    $_Output[(string) $xml->test_id]["txRefNum"] = $_returnXML->txRefNum;

                    $_Output[(string) $xml->test_id]["order_id"] = $order->getId();
                }
            }
            break;
            /******************************************************************************************************/
            /* CAPTURE
            /******************************************************************************************************/
            case "capture":
            {
                // We trying to do capture
                $_reference_transaction_xml = $_Output[(string) $xml->payment_actions->ref]["org_request"];
                $_Output[(string) $xml->test_id]["order_id"] = $_Output[(string) $xml->payment_actions->ref]["order_id"];

                $_price = (float)$_Output[(string) $xml->payment_actions->ref]["amount"];

                if (strtolower((string) $xml->payment_actions->capture) != "full")
                {
                    // We had partial capture
                    $_price = (float) $xml->payment_actions->capture;
                }

                $order = Mage::getModel("sales/order")->load($_Output[(string) $xml->test_id]["order_id"]);

                if (!$order)
                {
                    throw new Exception("Unable to find related order");
                } else {

                    if (strtolower((string) $xml->payment_actions->capture) != "full")
                    {
                        // Save old sub total
                        $subTotal = $order->getSubtotal();

                        $order->setSubtotal($_price)
                            ->setBaseSubtotal($_price)
                            ->setGrandTotal($_price)
                            ->setBaseGrandTotal($_price);

                        $order->save();
                    }

                    if($order->canInvoice())
                    {
                        try
                        {
                            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                            $invoice->register();

                            $transactionSave = Mage::getModel('core/resource_transaction')
                                ->addObject($invoice)
                                ->addObject($invoice->getOrder());

                            $transactionSave->save();
                        } catch (Exception $error)
                        {

                        }

                        //var_dump($invoice->getPayment()->getMethodInstance()->_soapResponse);
                        // Save the result
                        $_returnXML = $invoice->getOrder()->getPayment()->getMethodInstance()->_soapResponse->SOAPBody->MFCResponse->return;
                        $_Output[(string) $xml->test_id]["amount"] = $price;
                        $_Output[(string) $xml->test_id]["auth_code"] = "";
                        $_Output[(string) $xml->test_id]["resp_code"] = $_returnXML->respCode;
                        $_Output[(string) $xml->test_id]["asv__code"] = "";
                        $_Output[(string) $xml->test_id]["cvv__code"] = "";
                        $_Output[(string) $xml->test_id]["txRefNum"] = $_returnXML->txRefNum;
                    }

                    if (strtolower((string) $xml->payment_actions->capture) != "full")
                    {
                        $order->setSubtotal($subTotal)
                            ->setBaseSubtotal($subTotal)
                            ->setGrandTotal($subTotal)
                            ->setBaseGrandTotal($subTotal);

                        $order->save();
                    }
                }
            }
                break;
            /******************************************************************************************************/
            /* OTHER
            /******************************************************************************************************/
            default:
            {
                echo "Unspported Transaction Type Detected -> ".print_r($xml, true);
                die(0);
            }
            break;

        }


    } catch (Exception $e) {
        $_Output[(string) $xml->test_id]['request_card'] = " Exception " . $e->getMessage();

        }
    }

    // Finally, export to CSV file
    $file = "certification_report.csv";
    $f = fopen($file, 'w');

    // Write out the Header
    fwrite($f, "#,Card,AVS Zip,CVD,Amt,Auth Code,Resp Code,AVS Resp,CVV Resp,TxRefNum (40 character value in response)\r\n");

    foreach ($_Output as $output)
    {
        fwrite($f, $output["request_id"].",");
        fwrite($f, $output["request_card"].",");
        fwrite($f, $output["avs_zip"].",");
        fwrite($f, $output["cvd"].",");
        fwrite($f, $output["amount"].",");
        fwrite($f, $output["auth_code"].",");
        fwrite($f, $output["resp_code"].",");
        fwrite($f, $output["asv__code"].",");
        fwrite($f, $output["cvv__code"].",");
        fwrite($f, $output["txRefNum"]."\r\n");
    }
    fclose($f);
} catch (Exception $ex) {
    print_r($ex->getMessage());
}

