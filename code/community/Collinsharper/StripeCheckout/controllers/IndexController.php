<?php

class Collinsharper_StripeCheckout_IndexController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {
        echo 'Hello developer...';
    }

    /**
     * Get Option values from option id
     *
     * @param $options
     * @return stdClass
     */
    protected function _getOptionValues($options)
    {

        $optionCode = array(
            9 => 'reel_id', // Reel & Viewer reel_id
            11 => 'reel_id', // Reel only reel_id
            3 => 'viewer_color',
            44 => 'box_color',
        );

        $viewerColor = array(
            59 => 'Red',
            55 => 'Black',
            14 => 'Blue',
            58 => 'White',
            120 => 'Red - Happy Birthday',
            121 => 'Black - Happy Birthday',
            122 => 'Blue - Happy Birthday',
            123 => 'White - Happy Birthday',
            124 => 'Red - I Love You',
            125 => 'Black - I Love You',
            126 => 'Blue - I Love You',
            127 => 'White - I Love You',
        );

        $boxColor = array(
            128 => 'White',
            138 => 'Black',
            158 => 'Red',
            159 => 'Green',
            160 => 'Pink',
            185 => 'Blue',
        );

        $optionObject = new stdClass();
        foreach($options as $code => $option) {
            switch($code) {
                case 11:
                case 9:
                    $optionObject->reel_id = $option;
                    $reel = Mage::getModel('chreels/reels')->load($option);
                    $optionObject->reel_name = $reel->getData('reel_name');
                    $optionObject->reel_thumb = $reel->getData('thumb');
                    break;
                case 3:
                    $optionObject->viewer_color = $viewerColor[$option];
                    break;
                case 44:
                    $optionObject->box_color = $boxColor[$option];
                    break;
                default:
                    break;
            }
        }

        return $optionObject;
    }

    /**
     * Check is valid reward point
     * @param $sp
     * @return bool
     */
    protected function _isValidRewardPoint($sp)
    {
        if ($sp < 0) {
            $customer = Mage::getSingleton('rewards/session')->getSessionCustomer();
            if ($customer->getId()) {
                Mage::helper('rewards')->logException(
                    "Customer {$customer->getEmail()} (ID: {$customer->getId(
                    )}) tried hacking the points system by forcing JS calls to spend {$sp} points!"
                );
            }

            return false;
        }

        return true;
    }

    /**
     * GiftCard Validation function
     *
     * @param null $storeId
     * @return mixed
     * @throws Mage_Core_Exception
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _initGiftCard($storeId = null)
    {
        // Check if giftcard_code param comes
        $giftcardCode = $this->getRequest()->getParam('giftcard_code', null);
        if (null === $giftcardCode) {
            echo json_encode ( array (
                'code' => 1,
                'msg' => $this->__('Please enter gift card code.')
            ) ); exit;
        }

        // Check if giftcard_code exist
        $giftcardModel = Mage::getModel('aw_giftcard/giftcard')->loadByCode(trim($giftcardCode));
        if (null === $giftcardModel->getId()) {
            echo json_encode ( array (
                'code' => 2,
                'msg' => $this->__('Gift Card "%s" is not valid.', Mage::helper('core')->escapeHtml($giftcardCode))
            ) ); exit;
        }

        // Check if giftcard_code is valid for redeem
        if ($giftcardModel->getState() != AW_Giftcard_Model_Source_Giftcard_Status::AVAILABLE_VALUE) {
            if (null === $giftcardModel->getState()) {
                echo json_encode ( array (
                    'code' => 2,
                    'msg' => $this->__(AW_Giftcard_Model_Source_Giftcard_Status::DEFAULT_ERROR_MESSAGE)
                ) ); exit;
            } else {
                $_errorMessage = Mage::getModel('aw_giftcard/source_giftcard_status')->getErrorMessage($giftcardModel->getState());
                echo json_encode ( array (
                    'code' => 2,
                    'msg' => $this->__($_errorMessage)
                ) ); exit;
            }
        }

        if ($giftcardModel->isExpired()) {
            echo json_encode ( array (
                'code' => 2,
                'msg' => $this->__(AW_Giftcard_Model_Source_Giftcard_Status::EXPIRED_ERROR_MESSAGE)
            ) ); exit;
        }

        if ($giftcardModel->getStatus() != AW_Giftcard_Model_Source_Product_Attribute_Option_Yesno::ENABLED_VALUE) {
            echo json_encode ( array (
                'code' => 2,
                'msg' => $this->__('Gift Card "%s" is not active.', Mage::helper('core')->escapeHtml($giftcardModel->getCode()))
            ) ); exit;
        }

        $store = Mage::app()->getStore($storeId);
        $website = $store->getWebsite();
        if ($giftcardModel->getWebsiteId() != $website->getId()) {
            echo json_encode ( array (
                'code' => 2,
                'msg' => $this->__(AW_Giftcard_Model_Source_Giftcard_Status::DEFAULT_ERROR_MESSAGE)
            ) ); exit;
        }

        Mage::register('current_giftcard', $giftcardModel, true);
        return $giftcardModel;
    }

    /**
     * Apply Gift Card API
     */
    public function applyGiftCardAction()
    {
        /*
         * curl --request POST --data 'giftcard_code=GC-3296-4524&quote_id=261050' https://www.image3d.com/retroviewer/stripecheckout/index/applyGiftCard
         */
        try {
            $giftcardModel = $this->_initGiftCard();

            if (!$this->getRequest()->getParam('status_flag', null)) {
                $quoteId = Mage::app ()->getRequest ()->getParam ( 'quote_id' );
                $quote = Mage::getModel('sales/quote')->load($quoteId);

                if ($quote->getId()) {
                    Mage::helper('aw_giftcard/totals')->addCardToQuote($giftcardModel, $quote);

                    echo json_encode ( array (
                        'code' => 0,
                        'msg' => $this->__('Gift Card "%s" has been added.', Mage::helper('core')->escapeHtml($giftcardModel->getCode()))
                    ) ); exit;
                } else {
                    echo json_encode ( array (
                        'code' => 1,
                        'msg' => "Quote doesn't exit"
                    ) ); exit;
                }
            } else {
                Mage::getSingleton('checkout/session')->setCurrentGiftCard($giftcardModel);
            }

        } catch (Exception $e) {
            echo json_encode ( array (
                'code' => 1,
                'msg' => $this->__($e->getMessage())
            ) ); exit;
        }
    }

    /**
     * Remove Gift Card API
     */
    public function removeGiftCardAction()
    {
        /*
         * curl --request POST --data 'giftcard_code=GC-3296-4524&quote_id=261050' https://www.image3d.com/retroviewer/stripecheckout/index/removeGiftCard
         */
        if ($giftcardCode = $this->getRequest()->getParam('giftcard_code', null)) {
            //$giftcardCode = base64_decode($giftcardCode);
            try {
                $quoteId = Mage::app ()->getRequest ()->getParam ( 'quote_id' );
                $quote = Mage::getModel('sales/quote')->load($quoteId);

                if ($quote->getId()) {
                    Mage::helper('aw_giftcard/totals')->removeCardFromQuote(trim($giftcardCode), $quote);

                    echo json_encode ( array (
                        'code' => 0,
                        'msg' => $this->__('Gift Card "%s" has been removed.', Mage::helper('core')->escapeHtml($giftcardCode))
                    ) ); exit;
                } else {
                    echo json_encode ( array (
                        'code' => 1,
                        'msg' => "Quote doesn't exit"
                    ) ); exit;
                }
            } catch (Exception $e) {
                echo json_encode ( array (
                    'code' => 1,
                    'msg' => $this->__($e->getMessage())
                ) ); exit;
            }

        }
    }

    /**
     * Get Quote Gift Card Information by quote_id
     */
    public function getQuoteGiftCardAction()
    {
        /*
         * curl --request POST --data 'quote_id=261050' https://www.image3d.com/retroviewer/stripecheckout/index/getQuoteGiftCard
         */
        $quoteId = $this->getRequest()->getParam("quote_id");
        $giftCards = Mage::helper('aw_giftcard/totals')->getQuoteGiftCards($quoteId);

        $quoteCard = array();
        if ($giftCards) {
            foreach ($giftCards as $card) {
                $quoteCard[] = array(
                    'giftcard_amount' => $card->getGiftcardAmount(),
                    'code' => $card->getCode()
                );
            }
            echo json_encode ( array (
                'code' => 0,
                'applied_giftcard' => $quoteCard
            ) ); exit;
        } else {
            echo json_encode ( array (
                'code' => 1,
                'msg' => "Quote doesn't have applied Gift Card or Redemption Code"
            ) ); exit;
        }
    }

    /**
     * Apply Rewards point discount API
     */
    public function applyRewardPointAction()
    {
        /*
         * curl --request POST --data 'points_spending=200&quote_id=261050' https://www.image3d.com/retroviewer/stripecheckout/index/applyRewardPoint
         */

        $rewardPoints = $this->getRequest()->getParam("points_spending");
        $quoteId = Mage::app ()->getRequest ()->getParam ( 'quote_id' );
        $quote = Mage::getModel('sales/quote')->load($quoteId);

        if ($quote->getId()) {
            if ($this->_isValidRewardPoint($rewardPoints)) {
                $quote->setPointsSpending($rewardPoints);
                $quote->save();

                echo json_encode ( array (
                    'code' => 0,
                    'msg' => $this->__('"%s" Reward point has been applied.', Mage::helper('core')->escapeHtml($rewardPoints))
                ) ); exit;
            } else {
                echo json_encode ( array (
                    'code' => 2,
                    'msg' => "Reward point is invalid"
                ) ); exit;
            }
        } else {
            echo json_encode ( array (
                'code' => 1,
                'msg' => "Quote doesn't exit"
            ) ); exit;
        }
    }

    /**
     * Get applied Rewards point API
     */
    public function getAppliedRewardPointAction()
    {
        /*
         * curl --request POST --data 'quote_id=261050' https://www.image3d.com/retroviewer/stripecheckout/index/getAppliedRewardPoint
         */

        $quoteId = Mage::app ()->getRequest ()->getParam ( 'quote_id' );
        $quote = Mage::getModel('sales/quote')->load($quoteId);

        if ($quote->getId()) {
            echo json_encode ( array (
                'code' => 0,
                'applied_point' => $quote->getPointsSpending(),
            ) ); exit;
        } else {
            echo json_encode ( array (
                'code' => 1,
                'msg' => "Quote doesn't exit"
            ) ); exit;
        }
    }

    /**
     * Get Reward Points by Customer
     */
    public function getRewardPointsByCustomerAction()
    {
        /*
         * curl --request POST --data 'customer_id=177064' https://www.image3d.com/retroviewer/stripecheckout/index/getRewardPointsByCustomer
         */
        $customerId = Mage::app ()->getRequest ()->getParam ( 'customer_id' );

        if (Mage::helper('core')->isModuleEnabled('TBT_Rewards')) {
            $customer = Mage::getModel('rewards/customer');
            $customer = $customer->load($customerId);
            if (!$customer->getId()) {
                echo json_encode ( array (
                    'code' => 1,
                    'msg' => $this->__("No such customer with id %s exists.", $customerId)
                ) );
            } else {
                echo json_encode ( array (
                    'code' => 0,
                    'reward_points' => $customer->getUsablePoints()
                ) );
            }
        } else {
            echo json_encode ( array (
                'code' => 2,
                'msg' => $this->__("TBT_Rewards module is removed or disabled")
            ) );
        }
    }

    /**
     * Get earned rewards point by quote_id
     */
    public function getRewardsPointsEarnedOnQuoteAction()
    {
        /*
         * curl --request POST --data 'quote_id=261050' https://www.image3d.com/retroviewer/stripecheckout/index/getRewardsPointsEarnedOnQuote
         */

        $quoteId = Mage::app ()->getRequest ()->getParam ( 'quote_id' );
        $quote = Mage::getModel('sales/quote')->load($quoteId);

        if ($quote->getId()) {
            $points_earning = Mage::getSingleton ( 'rewards/session' )->getTotalPointsEarnedOnCart($quote);

            $points = 0;
            foreach ($points_earning as $point) {
                $points += $point;
            }

            echo json_encode ( array (
                'code' => 0,
                'earned_point' => $points,
                'msg' => $this->__('You will earn %s Points', Mage::helper('core')->escapeHtml($points))
            ) ); exit;
        } else {
            echo json_encode ( array (
                'code' => 1,
                'msg' => "Quote doesn't exit"
            ) ); exit;
        }
    }

    /**
     * Get Order item details by order increment_id
     */
    public function getOrderItemsByIncrementAction()
    {
        /*
         * curl --request POST --data 'increment_id=28880103546' https://www.image3d.com/retroviewer/stripecheckout/index/getOrderItemsByIncrement
         */
        $incrementId = Mage::app ()->getRequest ()->getParam ( 'increment_id' );

        if ( !empty($incrementId)) {

            $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
            $orderId = $order->getId();

            if ($orderId) {
                $items = array();
                foreach($order->getAllVisibleItems() as $item){
                    $itemObject = (object) $item->getData();

                    $options = $this->_getOptionValues($item->getBuyRequest()->getOptions());
                    $itemObject->options = $options;

                    $items[] = $itemObject;
                }

                echo json_encode ( array (
                    'code' => 0,
                    'order_id' => $orderId,
                    'items' => $items
                ) );
            } else {
                echo json_encode ( array (
                    'code' => 1,
                    'msg' => "Order doesn't exit"
                ) );
            }
        } else {
            echo json_encode ( array (
                'code' => 1,
                'msg' => 'increment_id is empty'
            ) );
        }

    }

    /**
     * Get Quote Id by Customer Id
     */
    public function getQuoteByCustomerAction()
    {
        /*
         * curl --request POST --data 'customer_id=177064&customer_email=rwang@collinsharper.com' https://www.image3d.com/retroviewer/stripecheckout/index/getQuoteByCustomer
         */
        $customerId = Mage::app ()->getRequest ()->getParam ( 'customer_id' );
        $customerEmail = Mage::app ()->getRequest ()->getParam ( 'customer_email' );

        if ( !empty($customerId) && !empty($customerEmail)) {

            $customer = Mage::getModel('customer/customer')->load($customerId);

            if ( $customer->getData('email') == $customerEmail ) {

                $quote = Mage::getModel('sales/quote')->loadByCustomer($customerId);
                $quoteId = $quote->getId();

                if ($quoteId) {
                    $items = array();
                    foreach($quote->getAllVisibleItems() as $item){
                        $itemObject = (object) $item->getData();

                        $options = $this->_getOptionValues($item->getBuyRequest()->getOptions());
                        $itemObject->options = $options;

                        $items[] = $itemObject;
                    }

                    echo json_encode ( array (
                        'code' => 0,
                        'quote_id' => $quoteId,
                        'items' => $items
                    ) );
                } else {
                    echo json_encode ( array (
                        'code' => 1,
                        'msg' => "Quote doesn't exit"
                    ) );
                }

            } else {
                echo json_encode ( array (
                    'code' => 2,
                    'msg' => 'Customer email is not matching with customer id'
                ) );
            }
        } else {
            echo json_encode ( array (
                'code' => 3,
                'msg' => 'Customer id & email is empty'
            ) );
        }
    }


    /*
     * Create cart and assign customer
     */
    /*
     * curl --request POST --data 'customer_email=rwang@collinsharper.com&customer_id=177064' https://www.image3d.com/retroviewer/stripecheckout/index/createEmptyCart
     *
     * response: {"code":0,"msg":"Cart created successfully","quote_id":"249003","customer_id":"177064","customer_email":"rwang@collinsharper.com"}
     */
    public function createEmptyCartAction()
    {
        $customerEmail = Mage::app()->getRequest()->getParam('customer_email');
        $customerId = Mage::app()->getRequest()->getParam('customer_id');

        $store = Mage::app()->getStore();
        $website = Mage::app()->getWebsite();
        $customer = Mage::getModel('customer/customer')->setWebsiteId($website->getId())->loadByEmail($customerEmail);

        if (!$customer->getId() || $customerId != $customer->getId()) {
            echo json_encode ( array (
                'code' => 1,
                'msg' => "Customer doesn't exit or matching with customer id and email"
            ) );
        } else {
            // initialize sales quote object
            $quote = Mage::getModel('sales/quote')->setStoreId($store->getId());

            // assign the customer to quote
            $quote->assignCustomer($customer);
            $quote->setCurrency(Mage::app()->getStore()->getBaseCurrencyCode());

            try {
                $quote->save();
                Mage::getSingleton('checkout/session')->setQuoteId($quote->getId());

                echo json_encode ( array (
                    'code' => 0,
                    'msg' => 'Cart created successfully',
                    'quote_id' => $quote->getId(),
                    'customer_id' => $customer->getId(),
                    'customer_email' => $customer->getEmail()
                ) );

            } catch (Exception $e) {
                echo json_encode ( array (
                    'code' => 1,
                    'msg' => $e->getMessage()
                ) );
            }
        }

    }


    /*
     * Create cart, add product and assign customer
     */
    /*
     * // For product id 9: Reel Only
     * curl --request POST --data 'customer_email=rwang@collinsharper.com&product_id=9&reel_id=974410' https://www.image3d.com/retroviewer/stripecheckout/index/createCart
     *
     * // For product id 10: Reel & Viewer Set
     * curl --request POST --data 'customer_email=rwang@collinsharper.com&product_id=10&reel_id=974410&viewer_color=59&box_color=138' https://www.image3d.com/retroviewer/stripecheckout/index/createCart
     *
     * response: {"code":0,"msg":"Cart created successfully","quote_id":"249003","customer_id":"177064","customer_email":"rwang@collinsharper.com","product_id":"9","reel_id":"974410","product_name":"Reels Only"}
     */
    public function createCartAction()
    {
        $customerEmail = Mage::app()->getRequest()->getParam('customer_email');
        $productId = Mage::app()->getRequest()->getParam('product_id');
        $reelId = Mage::app()->getRequest()->getParam('reel_id');
        $viewerColor = Mage::app()->getRequest()->getParam('viewer_color');
        $boxColor = Mage::app()->getRequest()->getParam('box_color');

        $store = Mage::app()->getStore();
        $website = Mage::app()->getWebsite();

        // initialize sales quote object
        $quote = Mage::getModel('sales/quote')->setStoreId($store->getId());

        $customer = Mage::getModel('customer/customer')->setWebsiteId($website->getId())->loadByEmail($customerEmail);

        if (!$customer->getId()) {
            echo json_encode ( array (
                'code' => 1,
                'msg' => "Customer doesn't exit"
            ) );
        } else {
            // assign the customer to quote
            $quote->assignCustomer($customer);
            $quote->setCurrency(Mage::app()->getStore()->getBaseCurrencyCode());

            $product = Mage::getModel('catalog/product')->load($productId);
            if (!$product->getId()) {
                echo json_encode ( array (
                    'code' => 1,
                    'msg' => "Product doesn't exit"
                ) );
            } else {
                try {
                    if ($productId == 9) { // 9: Reel Only
                        $buyInfo = array(
                            'product' => $productId,
                            'qty' => 1,
                            'options' => array(
                                11 => $reelId
                            )
                        );
                    } else { // 10: Reel & Viewer Set
                        $buyInfo = array(
                            'product' => $productId,
                            'qty' => 1,
                            'options' => array(
                                9 => $reelId,
                                3 => $viewerColor,
                                44 => $boxColor
                            )
                        );
                    }
                    $request = new Varien_Object();
                    $request->setData($buyInfo);
                    $quote->addProduct($product, $request);
                    //$quote->addProduct($product, 1);
                    $quote->save();

                    Mage::getSingleton('checkout/session')->setQuoteId($quote->getId());

                    echo json_encode ( array (
                        'code' => 0,
                        'msg' => 'Cart created successfully',
                        'quote_id' => $quote->getId(),
                        'customer_id' => $customer->getId(),
                        'customer_email' => $customer->getEmail(),
                        'product_id' => $product->getId(),
                        'reel_id' => $reelId,
                        'product_name' => $product->getName()
                    ) );

                } catch (Exception $e) {
                    echo json_encode ( array (
                        'code' => 1,
                        'msg' => $e->getMessage()
                    ) );
                }
            }
        }

    }


    /*
     * Complete Order
     */
    /*
     *
        curl --request POST \
     --data 'quote_id=256379&shipping_method=storepickupmodule_error&payment_method=cryozonic_stripe&firstname=james&lastname=lee&street0=335 S. Royal Ridge Dr.&street1=Anaheim&city=Anaheim&country_id=US&region=California&region_id=12&postcode=92807&telephone=2384690&platform=iOS&token=tok_1EGiuiK3zgwVZr39JgLzEkkz' \
    https://www.image3d.com/retroviewer/stripecheckout/index/completeOrder
     *
     * response: {"code":0,"msg":"Order created successfully","quote_id":"249003","grand_total":14.95,"currency":null,"order_id":"28880097469"}
     */
    function completeOrderAction() {
        $quoteId = Mage::app()->getRequest()->getParam('quote_id');
        $shippingMethodString = Mage::app()->getRequest()->getParam('shipping_method');
        $paymentMethodString = Mage::app()->getRequest()->getParam('payment_method');
        $token = Mage::app()->getRequest()->getParam('token');

        $firstname = Mage::app()->getRequest()->getParam('firstname');
        $lastname = Mage::app()->getRequest()->getParam('lastname');
        $street0 = Mage::app()->getRequest()->getParam('street0');
        $street1 = Mage::app()->getRequest()->getParam('street1');
        $city = Mage::app()->getRequest()->getParam('city');
        $country_id = Mage::app()->getRequest()->getParam('country_id');
        $region = Mage::app()->getRequest()->getParam('region');
        $region_id = Mage::app()->getRequest()->getParam('region_id');
        $postcode = Mage::app()->getRequest()->getParam('postcode');
        $telephone = Mage::app()->getRequest()->getParam('telephone');
        $platform = Mage::app()->getRequest()->getParam('platform');
        if (!$platform || $platform == '' || is_null($platform)) $platform = 'iOS';

        $billingAddress = array(
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => $firstname,
            'middlename' => '',
            'lastname' => $lastname,
            'suffix' => '',
            'company' => '',
            'street' => array(
                '0' => $street0, // required
                '1' => $street1 // optional
            ),
            'city' => $city,
            'country_id' => $country_id, // country code
            'region' => $region,
            'region_id' => $region_id,
            'postcode' => $postcode,
            'telephone' => $telephone,
            'fax' => '',
            'save_in_address_book' => 1
        );
        $shippingAddress = array(
            'customer_address_id' => '',
            'prefix' => '',
            'firstname' => $firstname,
            'middlename' => '',
            'lastname' => $lastname,
            'suffix' => '',
            'company' => '',
            'street' => array(
                '0' => $street0, // required
                '1' => $street1 // optional
            ),
            'city' => $city,
            'country_id' => $country_id, // country code
            'region' => $region,
            'region_id' => $region_id,
            'postcode' => $postcode,
            'telephone' => $telephone,
            'fax' => '',
            'save_in_address_book' => 1
        );

        $quote = Mage::getModel('sales/quote')->load($quoteId);

        if ($quote) {
            try {
                $billingAddressData = $quote->getBillingAddress()->addData($billingAddress);
                $shippingAddressData = $quote->getShippingAddress()->addData($shippingAddress);

                // collect shipping rates on quote
                $shippingAddressData->setCollectShippingRates(true)
                    ->collectShippingRates();

                if ($paymentMethodString == 'cryozonic_stripe') {
                    // set shipping method and payment method on the quote
                    $shippingAddressData->setShippingMethod($shippingMethodString)
                        ->setPaymentMethod($paymentMethodString);
                    // Set payment method for the quote
                    $quote->getPayment()->importData(
                        array(
                            'method' => $paymentMethodString,
                            'cc_stripejs_token' => $token
                        )
                    );
                } else {
                    // set shipping method and payment method on the quote
                    $shippingAddressData->setShippingMethod($shippingMethodString)
                        ->setPaymentMethod('checkmo');
                    // Set payment method for the quote
                    $quote->getPayment()->importData(array('method' => 'checkmo'));
                }

                // collect totals & save quote
                $quote->collectTotals()->save();

                // create order from quote
                $service = Mage::getModel('sales/service_quote', $quote);
                $service->submitAll();
                $incrementId = $service->getOrder()->getRealOrderId();

                // set order state as 'In Production'
                $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
                $order->setStatus('in_production')
                    ->setOrderSource($platform)
                    ->save();

                // Delete quote after create an order
                $quote->delete();

                // Update order grid table
                Mage::getModel('sales/order')->getResource()->updateGridRecords($order->getId());

                // send order confirmation email
                $order->getSendConfirmation(null);
                $order->sendNewOrderEmail();

                echo json_encode ( array (
                    'code' => 0,
                    'msg' => 'Order created successfully',
                    'quote_id' => $quote->getId(),
                    'grand_total' => $quote->collectTotals()->getData('grand_total'),
                    'currency' => $quote->getCurrency(),
                    'order_id' => $incrementId
                ) );

            } catch (Exception $e) {
                echo json_encode ( array (
                    'code' => 1,
                    'msg' => $e->getMessage()
                ) );
            }
        } else {
            echo json_encode ( array (
                'code' => 1,
                'msg' => "Quote doesn't exist"
            ) );
        }
    }

    /*
     * Create empty cart
     */
    /*
     * curl --request POST https://www.image3d.com/retroviewer/stripecheckout/index/createQuote
     *
     * response: {"code":0,"msg":"Created empty cart","quote_id":"725"}
     */
    public function createQuoteAction()
    {
        $store = Mage::app()->getStore();
        $website = Mage::app()->getWebsite();

        try {
            $quote = Mage::getModel('sales/quote')->setStoreId($store->getId());
            $quote->save();

            echo json_encode ( array (
                'code' => 0,
                'msg' => 'Created empty cart',
                'quote_id' => $quote->getId()
            ) );
        } catch (Exception $e) {
            echo json_encode ( array (
                'code' => 1,
                'msg' => $e->getMessage()
            ) );
        }
    }


    /*
     * Set customer to Cart
     */
    /*
     * curl --request POST --data 'quote_id=725&customer_id=137' https://www.image3d.com/retroviewer/stripecheckout/index/setCustomer
     *
     * response: {"code":0,"msg":"Set customer and currency","quote_id":"725","customer_id":"137","currency_code":"USD"}
     */
    public function setCustomerAction()
    {
        $quoteId = Mage::app()->getRequest()->getParam('quote_id');
        $customerId = Mage::app()->getRequest()->getParam('customer_id');

        $website = Mage::app()->getWebsite();

        // check whether the customer already registered or not
        $customer = Mage::getModel('customer/customer')->setWebsiteId($website->getId())->load($customerId);

        if (!$customer->getId()) {
            echo json_encode ( array (
                'code' => 1,
                'msg' => "Customer doesn't exit"
            ) );
        } else {
            $quote = Mage::getModel('sales/quote')->load($quoteId);

            try {
                // assign the customer to quote
                $quote->assignCustomer($customer);
                // set currency for the quote
                $quote->setCurrency(Mage::app()->getStore()->getBaseCurrencyCode());
                $quote->save();

                echo json_encode ( array (
                    'code' => 0,
                    'msg' => 'Set customer and currency',
                    'quote_id' => $quote->getId(),
                    'customer_id' => $customer->getId(),
                    'currency_code' => $quote->getCurrency()
                ) );
            } catch (Exception $e) {
                echo json_encode ( array (
                    'code' => 1,
                    'msg' => $e->getMessage()
                ) );
            }
        }
    }


    /*
     * Add product to Cart
     */
    /*
     * curl --request POST --data 'quote_id=725&product_id=906' https://www.image3d.com/retroviewer/stripecheckout/index/addProduct
     *
     * response: {"code":0,"msg":"Added product to cart","quote_id":"725","product_id":"906"}
     */
    public function addProductAction()
    {
        $quoteId = Mage::app()->getRequest()->getParam('quote_id');
        $productId = Mage::app()->getRequest()->getParam('product_id');
        $qty = 1;

        // check whether the product exist
        $product = Mage::getModel('catalog/product')->load($productId);
        if (!$product->getId()) {
            echo json_encode ( array (
                'code' => 1,
                'msg' => "Product doesn't exit"
            ) );
        } else {
            try {
                $quote = Mage::getModel('sales/quote')->load($quoteId);
                $quote->addProduct($product, $qty);
                $quote->save();

                foreach ($quote->getAllItems() as $item) {
                    $quoteProduct = $item->getProduct();

                    echo json_encode(array(
                        'code' => 0,
                        'msg' => 'Added product to cart',
                        'quote_id' => $quote->getId(),
                        'product_id' => $quoteProduct->getId()
                    ));
                }
            } catch (Exception $e) {
                echo json_encode ( array (
                    'code' => 1,
                    'msg' => $e->getMessage()
                ) );
            }
        }
    }


    /*
     * Set shipping method
     */
    /*
     * curl --request POST --data 'quote_id=725&shipping_method=flatrate_flatrate&payment_method=cryozonic_stripe' https://www.image3d.com/retroviewer/stripecheckout/index/setShippingMethod
     *
     * response: {"code":0,"msg":"Set shipping method","quote_id":"725","shipping_method":"flatrate_flatrate","payment_method":"cryozonic_stripe"}
     */
    public function setShippingMethodAction()
    {
        $quoteId = Mage::app()->getRequest()->getParam('quote_id');
        $shippingMethodString = Mage::app()->getRequest()->getParam('shipping_method');
        $paymentMethodString = Mage::app()->getRequest()->getParam('payment_method');

        try {
            $billingAddress = array(
                'customer_address_id' => '',
                'prefix' => '',
                'firstname' => 'Rudie',
                'middlename' => '',
                'lastname' => 'Wang',
                'suffix' => '',
                'company' => '',
                'street' => array(
                    '0' => 'Thunder River Boulevard', // required
                    '1' => 'Customer Address 2' // optional
                ),
                'city' => 'Teramuggus',
                'country_id' => 'US', // country code
                'region' => 'Alaska',
                'region_id' => '2',
                'postcode' => '99767',
                'telephone' => '123-456-7890',
                'fax' => '',
                'save_in_address_book' => 1
            );
            $shippingAddress = array(
                'customer_address_id' => '',
                'prefix' => '',
                'firstname' => 'Rudie',
                'middlename' => '',
                'lastname' => 'Wang',
                'suffix' => '',
                'company' => '',
                'street' => array(
                    '0' => 'Thunder River Boulevard', // required
                    '1' => 'Customer Address 2' // optional
                ),
                'city' => 'Teramuggus',
                'country_id' => 'US',
                'region' => 'Alaska',
                'region_id' => '2',
                'postcode' => '99767',
                'telephone' => '123-456-7890',
                'fax' => '',
                'save_in_address_book' => 1
            );

            $quote = Mage::getModel('sales/quote')->load($quoteId);

            // add billing address to quote
            $billingAddressData = $quote->getBillingAddress()->addData($billingAddress);
            // add shipping address to quote
            $shippingAddressData = $quote->getShippingAddress()->addData($shippingAddress);
            // collect shipping rates on quote
            $shippingAddressData->setCollectShippingRates(true)->collectShippingRates();
            // set shipping method and payment method on the quote
            $shippingAddressData->setShippingMethod($shippingMethodString)
                ->setPaymentMethod($paymentMethodString);

            $quote->save();

            echo json_encode ( array (
                'code' => 0,
                'msg' => 'Set shipping method',
                'quote_id' => $quote->getId(),
                'shipping_method' => $quote->getShippingAddress()->getShippingMethod(),
                'payment_method' => $quote->getShippingAddress()->getPaymentMethod()
            ) );
        } catch (Exception $e) {
            echo json_encode ( array (
                'code' => 1,
                'msg' => $e->getMessage()
            ) );
        }
    }


    /*
     * Set payment method
     */
    /*
     * curl --request POST --data 'quote_id=725&payment_method=cryozonic_stripe&token=src_1DwY0nK3zgwVZr3993i4ZKuH:Visa:5556' https://www.image3d.com/retroviewer/stripecheckout/index/setPaymentMethod
     *
     * response: {"code":0,"msg":"Set shipping method","quote_id":"725","payment_method":"cryozonic_stripe"}
     */
    public function setPaymentMethodAction()
    {
        $quoteId = Mage::app()->getRequest()->getParam('quote_id');
        $paymentMethodString = Mage::app()->getRequest()->getParam('payment_method');
        $token = Mage::app()->getRequest()->getParam('token');

        try {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($paymentMethodString == 'cryozonic_stripe') {
                // Set payment method for the quote
                $quote->getPayment()->importData(
                    array(
                        'method' => $paymentMethodString,
                        'cc_stripejs_token' => $token
                    )
                );
            } else {
                // Set payment method for the quote
                $quote->getPayment()->importData(array('method' => 'checkmo'));
            }

            echo json_encode ( array (
                'code' => 0,
                'msg' => 'Set payment method',
                'quote_id' => $quote->getId(),
                'payment_method' => $quote->getShippingAddress()->getPaymentMethod()
            ) );
        } catch (Exception $e) {
            echo json_encode ( array (
                'code' => 1,
                'msg' => $e->getMessage()
            ) );
        }
    }


    /*
     * Create order
     */
    /*
     * curl --request POST --data 'quote_id=725' https://www.image3d.com/retroviewer/stripecheckout/index/createOrder
     *
     * response: {"code":0,"msg":"Set shipping method","quote_id":"725","shipping_method":"flatrate_flatrate"}
     */
    public function createOrderAction()
    {
        $quoteId = Mage::app()->getRequest()->getParam('quote_id');

        try {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            // collect totals & save quote
            $quote->collectTotals()->save();

            // create order from quote
            $service = Mage::getModel('sales/service_quote', $quote);
            $service->submitAll();
            $increment_id = $service->getOrder()->getRealOrderId();


            echo json_encode ( array (
                'code' => 0,
                'msg' => 'Order created successfully',
                'order_id' => $increment_id
            ) );

        } catch (Exception $e) {
            echo json_encode ( array (
                'code' => 1,
                'msg' => $e->getMessage()
            ) );
        }
    }
}
