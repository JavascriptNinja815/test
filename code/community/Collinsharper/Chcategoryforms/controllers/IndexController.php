<?php
class Collinsharper_Chcategoryforms_IndexController extends Mage_Core_Controller_Front_Action
{

     CONST TAG_ORDER_ESTIMATE = 4;
     CONST TAG_ORDER_bTB_ORDER = 3;
     CONST TAG_ORDER_STEREO = 6;


     protected function _getSession()
     {
         return Mage::getSingleton('checkout/session');
     }

     protected function getCustomerSession()
     {
         return Mage::getSingleton('customer/session');
     }

     protected function _getCart()
     {
         return Mage::getSingleton('checkout/cart');
     }

     protected function _getQuote()
     {
         return $this->_getCart()->getQuote();
     }


     protected function btbOrderRedirectAction()
     {
         Mage::getSingleton('customer/session')->setBeforeAuthUrl(Mage::getUrl('order/order-form/', array('_secure' => true)));
         if($this->getRequest()->getParam('action', false) == 'register') {
             $this->_redirect('customer/account/create');
         } else {
             $this->_redirect('customer/account/login');
         }
         return;
     }

     public function createAddressAction()
     {
         $data = $this->getRequest()->getParams();
         $isAjax = $this->getRequest()->getParam('is_ajax', false);
         $isBilling = $this->getRequest()->getParam('is_billing', true);
         $customerId = Mage::getSingleton('customer/session')->getCustomerId();
         $websiteId = Mage::app()->getWebsite()->getId();

         $customer = Mage::getModel("customer/customer");
         $customer->setWebsiteId($websiteId);

         $customer->load($customerId);

         mage::log(__METHOD__ . __LINE__ ." wee have CID  " . $customer->getId());

         $errors = array();
         /* @var $address Mage_Customer_Model_Address */
         $address = Mage::getModel('customer/address');
         /* @var $addressForm Mage_Customer_Model_Form */
         $addressForm = Mage::getModel('customer/form');
         $addressForm->setFormCode('customer_register_address')
             ->setEntity($address);

         $addressData = $addressForm->extractData($this->getRequest(), 'shipping', false);

         mage::log(__METHOD__ . __LINE__ . " adrress data " . print_r($addressData,1));

         $addressErrors = $addressForm->validateData($addressData);
         if (is_array($addressErrors)) {
             $errors = array_merge($errors, $addressErrors);
         }
         $address->setId(null)
             ->setIsDefaultBilling($this->getRequest()->getParam('default_billing', false))
             ->setIsDefaultShipping($this->getRequest()->getParam('default_shipping', false));
         $addressForm->compactData($addressData);
         $customer->addAddress($address);

         if(!$address->getId()) {
             $address->setCustomerId($customer->getId());
             $address->save();
         }

         mage::log(__METHOD__ . __LINE__ . " wehave address COMPany  " . $address->getCompany());
         mage::log(__METHOD__ . __LINE__ . " wehave address IDd " . $address->getId());

         $addressErrors = $address->validate();

         if (is_array($addressErrors)) {
             $errors = array_merge($errors, $addressErrors);
         }

         if($isAjax) {
             if($isBilling) {
                 $block = Mage::app()->getLayout()->createBlock("checkout/onepage_billing")->setName('checkout.onepage.billing.form.custom')->setTemplate('chcategoryform/bill_address.phtml')->toHtml();
             } else {
                 $block = Mage::app()->getLayout()->createBlock("checkout/onepage_shipping")->setName('checkout.onepage.shipping.form.custom')->setTemplate('chcategoryform/address.phtml')->toHtml();

             }

             $paymentBlock = Mage::app()->getLayout()
                 ->createBlock('page/html')
                 ->setBlockId("bs_cards")
                 ->setTemplate('beanstreamprofiles/customer/form_cards.phtml')
                 ->toHtml();

             $response = array('errors' => $errors, 'success' => count($errors) == 0, 'address_id' => $address->getId(), 'block' => $block, 'payment_block' => $paymentBlock, );
             $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
             $this->getResponse()->setBody(json_encode($response));
             return;

         }


         if(count($errors)) {
             return $errors;
         } else {
             return $address->getId();
         }

     }

     protected function _initProduct()
     {
         $productId = (int) $this->getRequest()->getParam('product');
         if ($productId) {
             $product = Mage::getModel('catalog/product')
                 ->setStoreId(Mage::app()->getStore()->getId())
                 ->load($productId);
             if ($product->getId()) {
                 return $product;
             }
         }
         return false;
     }

     public function _createCustomerAccount($customerEmail, $data)
     {
        mage::log(__METHOD__ . __LINE__ . " STUB DATA");

         if(!$customerEmail) {
             $customerEmail = 'b2b_inquiry_' . time() . '_' . rand(999,9999) . '@image3d.com';
         }

         $websiteId = Mage::app()->getWebsite()->getId();
         $store = Mage::app()->getStore();

         $firstName = isset($data['customer']) && isset($data['customer']['firstname']) ? $data['customer']['firstname'] : 'Business';
         $lastName = isset($data['customer']['lastname']) ? $data['customer']['lastname'] : 'Inquiry';
         $customer = Mage::getModel("customer/customer");
         $customer->setWebsiteId($websiteId)
             ->setStore($store)
             ->setFirstname($firstName)
             ->setLastname($lastName)
             ->setEmail($customerEmail)
             ->setPassword(md5(rand(88888,9999999999)));

         try{
             $customer->save();
             return $customer;
         }
         catch (Exception $e) {
             Zend_Debug::dump($e->getMessage());
         }

         return false;
     }

     /* Kit: Updated function that does not required any cart session */
     public function processAction()
     {
         $return = array('success' => false);
         $websiteId = Mage::app()->getWebsite()->getId();
         $store = Mage::app()->getStore();
         $data = $this->getRequest()->getParams();
        $customerEmail = false;
        $customerName = false;
         $inquiryModel = Mage::getModel('chinquiry/source_inquirytype');
         try {
             Mage::log(__FILE__ . ' ' . __LINE__ . ' ' . print_r($data, true), null, 'kit.log');

             if (empty($data)) {
                $return['message'] = Mage::helper('core')->__('Invalid request');
                 echo json_encode($return);
                 exit;
             }

//             $quote = Mage::getModel('sales/quote')->setStoreId($store->getId());
//             if(Mage::getSingleton('customer/session')->getReserveOrderId()) {
//                 $quote->setData('reserved_order_id', Mage::getSingleton('customer/session')->getReserveOrderId());
//             }

             // Prepare the customer information
             if($this->getCustomerSession()->isLoggedIn()) {
                 mage::log(__METHOD__ . __LINE__ . " have customer ");
                 $customer = $this->getCustomerSession()->getCustomer();
                 $customer_email = $customer->getEmail();
                 $customerEmail = $customer->getEmail();

             } else {
                 mage::log(__METHOD__ . __LINE__ . " hunt customer ");
                 $customer_email = $data['customer']['email'];

                 if(!$customer_email) {
                     $customer_email = isset($data['billing']['email']) ? $data['billing']['email'] : false;
                 }

                 $customerEmail = $customer_email;
                 $firstName = isset($data['customer']) && isset($data['customer']['firstname']) ? $data['customer']['firstname'] : 'Business';
                 $lastName = isset($data['customer']['lastname']) ? $data['customer']['lastname'] : 'Inquiry';

                 $customerName = $firstName . ' ' . $lastName;

                 $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->loadByEmail($customer_email);
             }


             if (!$customer || !$customer->getId()) {
                 // Kl: Create a new customer
                 $customer = $this->_createCustomerAccount($customer_email, $data);
             }

             $data['customer_id'] = $customer->getId();
             $data['customer_email'] = $customer_email;
             $data['customer_name'] = $customer->getName();


             //     $quote->assignCustomer($customer);

       //      $product = mage::getModel('catalog/product')->load($data['product']);

             // Prepare the BugInfo
             $buyInfo = array(
                 'product' => $data['product'],
                 'related_product' => 'hello',
                 'bundle_option' => isset($data['bundle_option']) ? $data['bundle_option'] : false,
                 'bundle_option_qty' => isset($data['bundle_option_qty']) ? $data['bundle_option_qty'] : false,
                 'options' => isset($data['options']) ? $data['options'] : false,
                 'qty' => 1,
             );



             $data['buy_request'] = $buyInfo;
             // KL: Do we have any bundle information?
             /*$bundle_options = $data['bundle_option'];
             if(is_array($bundle_options)){
                 $bundled_items = array();

                 foreach ($bundle_options as $bundle_option_key => $bundle_option_value) {
                     $bundled_items[$bundle_option_key][] = $bundle_option_value;
                 }

                 $buyInfo['bundle_option'] = $bundled_items;
             }

             $bundle_options_qty = $data['bundle_option_qty'];
             if(is_array($bundle_options_qty)){
                 $bundled_item_qty = array();

                 foreach ($bundle_options_qty as $bundle_options_qty_key => $bundle_options_qty_value) {
                     $bundled_item_qty[$bundle_options_qty_key] = $bundle_options_qty_value;
                 }

                 $buyInfo['bundle_option_qty'] = $bundled_item_qty;
             }*/

             mage::log(__METHOD__ . __LINE__ . " looking for customer data " . print_r($customer->getData(), true), null, 'kit.log' );

             $isStereo = false;
             $stereoQuoteId = Mage::helper('checkout/cart')->getQuote()->getId();
             $guid = $this->getRequest()->getParam('guid', false);
             if($guid) {
                 $stereoQuoteId = $guid;
             }



             if(isset($data['shipping_address_id'] )) {
                 mage::log(__METHOD__ . __LINE__ . " Expected AID " . $data['shipping_address_id'] );
             }


             //echo " we have quote and address " . $quote->getShippingAddress()->getId()."\n";

             $email = $customer ? $customer->getEmail() : false;
             if(!$email) {
                 $email = isset($data['billing']['email']) ? $data['billing']['email'] : false;
             }

                 $inquiry = Mage::getModel('chinquiry/inquiry');
             $inquiry->setCustomerId($customer->getId());
             $inquiry->setQuoteId($stereoQuoteId);
         //    $inquiry->setIncrementId(Mage::getSingleton('customer/session')->getReserveOrderId());
             $inquiry->setIp(long2ip(Mage::helper('core/http')->getRemoteAddr(true)));
             $inquiry->setStoreId(Mage::app()->getStore()->getId());
             $inquiry->setName($customer->getName());
             $inquiry->setEmail($customer_email);
             $inquiry->setStatus(Collinsharper_Inquiry_Model_Source_Status::STATE_NEW);
             $inquiry->setpostData(serialize($data));
             if(isset($data['tag_stereo_order'])) {
                 $inquiry->setInquiryType(Collinsharper_Inquiry_Model_Source_Inquirytype::STEREO_INQUIRY);
             } else if (isset($data['tag_b2b_estimate'])) {
                 $inquiry->setInquiryType(Collinsharper_Inquiry_Model_Source_Inquirytype::ESTIMATE_INQUIRY);
             } else if (isset($data['tag_b2b_order'])) {
                 $inquiry->setInquiryType(Collinsharper_Inquiry_Model_Source_Inquirytype::ORDER_INQUIRY);
             }
             $inquiry->save();
             $return['order_id'] = $inquiry->getId();
             $return['success'] = true;

             Mage::getSingleton('customer/session')->unsReserveOrderId();


             //$return['order_amount'] = 'UNKNOWN';

             $emailData = $inquiryModel->buildInquiryEmailData($data, $inquiry, $customerEmail, $customerName);
             $emailData->setData('inquiry_id', $inquiry->getId());
             mage::log(__METHOD__  ." and emaildata " . serialize($emailData->getData())  );

             $emailResult = $inquiryModel->sendInquiryEmailData($emailData);

             // TODO : transfer images from quote to order customer_entity_chuploads
             // TODO : send custom confirmation email for the stereo order form (*like on the ticket)

             $subscribe = false;
             // stereo subscribe
             $subscribe = isset($data['newsletter_subscribe']) && $data['newsletter_subscribe'];
             if($subscribe) {
                 $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($customer->getEmail());
                 if (!$subscriber->getId()
                     || $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED
                     || $subscriber->getStatus() == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
                     $subscriber->setStatus(Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED);
                     $subscriber->setSubscriberEmail($customer->getEmail());
                     $subscriber->setSubscriberConfirmCode($subscriber->RandomSequence());
                 }
                 $subscriber->setStoreId(Mage::app()->getStore()->getId());
                 $subscriber->setCustomerId($customer->getId());
                 try {
                     $subscriber->save();
                 } catch (Exception $e) {
                     //TODO: Email store owner here
                     $return['message'] =  "Exception: " . $e->getMessage(). "\n";
                     $return['success'] =  false;
                 }

             }

         } catch (Exception $e) {
             //TODO: Email store owner here
             $return['message'] =  "Exception: " . $e->getMessage(). "\n";
             $return['success'] =  false;
         }



         echo json_encode($return);
         exit;




     }

     /* This function has been disabled for backup purposes */
     public function _processAction()
     {
         // TODO : log the post as serialized to a csv file with the date and CID


         // CART FIRST

         $websiteId = Mage::app()->getWebsite()->getId();
         $store = Mage::app()->getStore();
         $cart = $this->_getCart();


         try {
             if (isset($params['qty'])) {
                 $filter = new Zend_Filter_LocalizedToNormalized(
                     array('locale' => Mage::app()->getLocale()->getLocaleCode())
                 );
                 $params['qty'] = $filter->filter($params['qty']);
             }

             $product = $this->_initProduct();
             $related = $this->getRequest()->getParam('related_product');

             Mage::log(__FILE__ . ' ' . __LINE__ . ' ' . print_r($product->getId(), true), null, 'kit.log');

             // KL: Do we have any bundle information?
             $bundle_options = $this->getRequest()->getPost('bundle_option');
             if(is_array($bundle_options)){
                 $bundled_items = array();

                foreach ($bundle_options as $bundle_option_key => $bundle_option_value) {
                    $bundled_items[$bundle_option_key][] = $bundle_option_value;
                }

                $params['bundle_option'] = $bundled_items;
             }

             Mage::log(__FILE__ . ' ' . __LINE__ . ' ' . print_r($params, true), null, 'kit.log');

             /**
              * Check product availability
              */
             if (!$product) {
                 $this->_goBack();
                 return;
             }

             $cart->addProduct($product, $params);
             if (!empty($related)) {
                 $cart->addProductsByIds(explode(',', $related));
             }

             $cart->save();

             $this->_getSession()->setCartWasUpdated(true);

             /**
              * @todo remove wishlist observer processAddToCart
              */
             Mage::dispatchEvent('checkout_cart_add_product_complete',
                 array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
             );


//             if (!$this->_getSession()->getNoCartRedirect(true)) {
//                 if (!$cart->getQuote()->getHasError()){
//                     $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
//                     $this->_getSession()->addSuccess($message);
//                 }
//                 $this->_goBack();
//             }

         } catch (Mage_Core_Exception $e) {
             if ($this->_getSession()->getUseNotice(true)) {
                 $this->_getSession()->addNotice(Mage::helper('core')->escapeHtml($e->getMessage()));
             } else {
                 $messages = array_unique(explode("\n", $e->getMessage()));
                 foreach ($messages as $message) {
                     $this->_getSession()->addError(Mage::helper('core')->escapeHtml($message));
                 }
             }

             $url = $this->_getSession()->getRedirectUrl(true);
             if ($url) {
                 $this->getResponse()->setRedirect($url);

             } else {
                 $this->_redirectReferer(Mage::helper('checkout/cart')->getCartUrl());
             }
         } catch (Exception $e) {
             $this->_getSession()->addException($e, $this->__('Cannot add the item to shopping cart.'));
             Mage::logException($e);
             $this->_goBack();
         }



         // CUSTOMER PART

         $params = $this->getRequest()->getParams();
         mage::log(__METHOD__ . " params " . print_r($params,1));

         mage::log(__METHOD__ . __LINE__ . " TEST DIE ... ");
         $customer = $this->getCustomerSession()->getCustomer();
         if(!$customer || $customer->getId()) {
             //TODO: multiple forms post here verify emial param path
             $customerEmail = isset( $params['customer']) && isset( $params['customer']['email']) ? $params['customer']['email']  : false ;


             if($customerEmail) {
                 $customer = Mage::getModel("customer/customer");
                 $customer->setWebsiteId($websiteId)
                     ->setStore($store)
                     ->loadByEmail($customerEmail);

             }

             if(!$customer|| $customer->getId()) {
                 // TODO : create an accoutn with email or with a generic i3d email
                 $customer = $this->_createCustomerAccount($customerEmail, $params);

             }

         }

         // ORDER PART

         try {


             $quote = $this->_getQuote();

             if (!$quote->getId()) {
                 throw new Exception(Mage::helper('payment')->__("Invalid quote id %s, cannot be found!"));
             }

             mage::log(__METHOD__ . __LINE__);

             $billing = false;

             if(isset($params['shipping_address_id'])) {
                 $customerBilling = Mage::getModel('customer/address')->load($params['shipping_address_id']);
                 $billing = Mage::getModel('sales/quote_address')
                     ->importCustomerAddress($customerBilling);
             }

             $customer = Mage::getModel('customer/customer');
             $customer->loadByEmail($customerEmail);

             mage::log(__METHOD__ . __LINE__ . " looking for customer data " . print_r($customer->getData(), true), null, 'kit.log' );
             if(!$billing) {
                 // TODO : we need to fake an address or the order wont setup.
                 // use I3d address
                 $addressData =  array (
                    'firstname' => $customer->getFirstname(),
                    'lastname' => $customer->getLastname(),
                    'company' => 'No Address Provided',
                    'street' => 'No Address Provided',
                    'city' => 'No Address Provided',
                    'country_id' => 'US',
                    'region' => 'Oregon',
                    'region_id' => 49,
                    'postcode' => '97004',
                    'telephone' => '5036322470',
                );

                 $customerBilling = Mage::getModel('customer/address');
                 $customerBilling->addData($addressData);
                 $customer->addAddress($customerBilling);

                 $customer->save();

                 $billing = Mage::getModel('sales/quote_address')
                     ->importCustomerAddress($customerBilling);
             } else {
                // Make sure we had the right address
                 $address_id = $billing->getData('address_id');
                 $customerBilling = Mage::getModel('customer/address')->load($address_id);
                 $customerBilling->setData('firstname', $customer->getFirstname());
                 $customerBilling->setData('lastname', $customer->getLastname());
                 $customerBilling->setId($address_id);
                 $customerBilling->save();

                 $billing = Mage::getModel('customer/address')->load($address_id);
             }

             mage::log(__METHOD__ . __LINE__ . " looking for billing data " . print_r($billing->getData(), true), null, 'kit.log' );

             $quote->setShippingAddress($billing);
             $quote->setBillingAddress($billing);
             $quote->save();

             $billing    = $quote->getBillingAddress();
             $shipping   = $quote->isVirtual() ? null : $quote->getShippingAddress();

             mage::log(__METHOD__ . __LINE__ . " looking for billing data " . print_r($billing->getData(), true), null, 'kit.log' );

             mage::log(__METHOD__ . __LINE__ . " looking for ship data " . $shipping->getId() );
             mage::log(__METHOD__ . __LINE__ . " Expected AID " . $params['shipping_address_id'] );

             if (isset($customerBilling) && !$customer->getDefaultBilling()) {
                 $customerBilling->setIsDefaultBilling(true);
             }
             if ($shipping && isset($customerShipping) && !$customer->getDefaultShipping()) {
                 $customerShipping->setIsDefaultShipping(true);
             } else if (isset($customerBilling) && !$customer->getDefaultShipping()) {
                 $customerBilling->setIsDefaultShipping(true);
             }
             $quote->setCustomer($customer);


             echo " we have quote and address " . $quote->getShippingAddress()->getId()."\n";

             $shipping
                 ->setCollectShippingRates(true)
                 ->collectShippingRates()
                 ->setShippingMethod('flatrate_flatrate')
                 ->setPaymentMethod('checkmo');
             $quote->getPayment()->importData(array('method' => 'checkmo'));
             $quote->collectTotals()->save();

             echo ' we have ship? ' . $shipping->getShippingMethod() ."\n";

             $service = Mage::getModel('sales/service_quote', $quote);
             $service->submitAll();

             $order = $service->getOrder();
             echo  " we got an order " . $order->getId() . "\n";


         } catch (Exception $e) {
             echo " we had exception " . $e->getMessage(). "\n";
         }


         // TODO : handle subsribe newsletter checkbox
         // TODO : transfer images from quote to order customer_entity_chuploads
         // TODO : send custom confirmation email for the stereo order form (*like on the ticket)
         // TODO : fix order edit to use ch beanstream paymeent module
         // TODO : put the field q94_notesOr in to the order comments as from the customer




     }

     public function checkAction(){
         //#193 check if customer not logged in on stereo form
         if(!Mage::getSingleton('customer/session')->isLoggedIn()){
             $massage = $this->__('Please login and try again.');
             $result['message'] =  $massage;
             $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
         }
     }


}
