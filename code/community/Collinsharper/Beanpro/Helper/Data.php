<?php

class Collinsharper_Beanpro_Helper_Data extends Mage_Core_Helper_Abstract
{

    var $_customer;

    const TRANS_TABLE = 'collinsharper_beanpro_transaction';
    const PROFILE_TABLE = 'collinsharper_beanpro_profiles';

    public function getTestCards()
    {
        return  array(
            'Visa - 4030000010001234 Approved',
            'Visa - 4504481742333 Approved $100 Limit (*legato)',
            'Visa - 4123450131003312 with VBV passcode 12345 Approved VBV',
            'Visa - 4003050500040005 Declined',
            'MC - 5100000010001004 Approved',
            'MC - 5194930004875020 Approved',
            'MC - 5123450000002889 Approved',
            'MC - 5123450000000000 passcode 12345 3D Secure Approved',
            'MC -5100000020002000 Declined',
            'Amex - 371100001000131 Approved',
            'Amex - 342400001000180 Declined',
        );
    }
    public function getDebug()
    {
        return Mage::getStoreConfig('payment/beanpro/debug');
    }

    public function getTest()
    {
        return Mage::getStoreConfig('payment/beanpro/test');
    }

    public function getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    public function getCustomer()
    {
        if (empty($this->_customer)) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }

    public function completeVbvTransaction($params)
    {

        if($this->getDebug() || $this->getTest())
        {
            mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r($params,1));
        }

        $postdata = array(
            'PaRes' => $params['PaRes'],
            'MD' => $params['MD'],
        );
        $url = "https://www.beanstream.com/scripts/process_transaction_auth.asp";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($postdata));
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,2);
        $qresult = curl_exec($ch);
        parse_str($qresult,$return);

        if($this->getDebug() || $this->getTest())
        {
            mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r($qresult,1));
            mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r($return,1));
        }
        return $return;
    }

    public function customerHasAddresses()
    {
        return count($this->getCustomer()->getAddresses());
    }

    public function getCcAvailableTypes()
    {
        $types =  Mage::getSingleton('payment/config')->getCcTypes();
        $availableTypes = Mage::getStoreConfig('payment/beanpro/cctypes');
        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);
            foreach ($types as $code=>$name) {
                if (!in_array($code, $availableTypes)) {
                    unset($types[$code]);
                }
            }
        }
        return $types;
    }

    public function getAddressesHtmlSelect($type = 'billing')
    {
        if ($this->isCustomerLoggedIn()) {
            $options = array();
            foreach ($this->getCustomer()->getAddresses() as $address) {
                $options[] = array(
                    'value'=>$address->getId(),
                    'label'=>$address->format('oneline')
                );
            }

            if (empty($addressId)) {
                if ($type=='billing') {
                    $address = $this->getCustomer()->getPrimaryBillingAddress();
                } else {
                    $address = $this->getCustomer()->getPrimaryShippingAddress();
                }
                if ($address) {
                    $addressId = $address->getId();
                }
            }

            $select = Mage::app()->getLayout()->createBlock('core/html_select')
                ->setName($type.'_address_id')
                ->setId($type.'-address-select')
                ->setClass('address-select')
            //      ->setExtraParams('onchange="'.$type.'.newAddress(!this.value)"')
                ->setValue($addressId)
                ->setOptions($options);

            //   $select->addOption('', Mage::helper('checkout')->__('New Address'));

            return $select->getHtml();
        }
        return '';
    }

    public function getReciept()
    {
        $session = $this->getSession();
        $order = Mage::getModel('sales/order');
        $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        if('beanpro' != (string)$order->getPayment()->getMethod())
        {
            return false;
        }

        if(Mage::getSingleton('customer/session')->getInternetSecureccData())
        {
            $bits = Mage::getSingleton('customer/session')->getInternetSecureccData(true);
            $this->saveReceipt($bits);
            return $bits;
        }
    }

    public function isGuestCheckout()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getCheckoutMethod() == Mage_Checkout_Model_Type_Onepage::METHOD_GUEST;
    }

    public function _getTable()
    {
        return Mage::getSingleton('core/resource')->getTablename('collinsharper_beanpro');
    }

    public function _cw()
    {
        return Mage::getSingleton('core/resource')
            ->getConnection('core_write');
    }

    public function loadAccountById($id)
    {
        $sql = "select * from ".$this->_getTable()." where data_key = '{$id}'";
        return $this->_cw()->fetchAll($sql);
    }

    public function loadStoredAccountId($cid)
    {
        // maybe not the best but it handles expired for now
        // $sql = "delete from ".$this->_getTable()." where    to_days(concat('20',substring(card_expiry_MMYY,3,2),'-',substring(card_expiry_MMYY,1,2),'-27')) <  to_days(now()) ";
        // $x = $this->_cw()->query($sql);
        $sql = "select * from ".$this->_getTable()." where customer_id = $cid";
        return $this->_cw()->fetchAll($sql);
    }

    public function _getAdminSession()
    {
        return Mage::getSingleton('admin/session');
    }

    public function _getAdminSessionQuote()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }

    public function _getQuoteCustomerId()
    {
        $session = $this->getSession();
        $customer_id = false;
        // if they are not logged in as customer and as an admin w/ a valid admin quote )
        if(!$session->isLoggedIn() && $this->_getAdminSession()->isLoggedIn() && $this->_getAdminSessionQuote()->getCustomerId())
        {
            $customer_id = $this->_getAdminSessionQuote()->getCustomerId();
        }

        if($session->isLoggedIn() && !$this->isGuestCheckout())
        {

            $customer_id = $this->getSession()->getCustomer()->getId();
        }
        if(!$customer_id)
        {
            return false;
        }
        //mage::log(__CLASS__ . __LINE__ . " cid " . $customer_id);

        return $customer_id;
    }

    public function getStoredAccountId()
    {
        $customer_id = $this->_getQuoteCustomerId();

        if(!$customer_id)
        {
            return false;
        }

        $record = $this->loadStoredAccountId($customer_id);
        return isset($record[0]) && isset($record[0]['customer_id']) ? $record : false;
    }

    public function _productLine($amount, $qty, $id, $desc, $flags = '')
    {
        $id = preg_replace('/[^a-z0-9\s]+/i','',$id);
        $desc = preg_replace('/[^a-z0-9\s]+/i','',$desc);
        return sprintf("%01.2f",$amount).'::'.floor($qty).'::'.substr($id,0,50).'::'.substr($desc,0,250).'::'.$flags;
    }

    public function loadPaymentData($oid)
    {
        $table = Mage::getSingleton('core/resource')->getTablename('collinsharper_beanpro_transaction');
        $sql = "select * from {$table} where `order_id` = '{$oid}' order by create_at desc limit 1";
        if($this->isTest())
        {
            mage::log(__CLASS__ . " getting Tx bits " . $sql);
        }
        $data = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);
        if($this->isTest())
        {
            mage::log(__CLASS__ . " bits " . print_r($data,1));
        }
        return $data[0];
    }

    public function setTokenDefault($tokenId)
    {
        $table = $this->_getTable();

        $sql = "select customer_id from {$table} where data_key = '{$tokenId}' limit 1";
        $customer_id =  Mage::getSingleton('core/resource')->getConnection('core_read')->fetchOne($sql);
        $sql = "update {$table} set `default_card` = 0 where customer_id ='{$customer_id}'";
        $d =  Mage::getSingleton('core/resource')->getConnection('core_write')->query($sql);

        $sql = "update {$table} set `default_card` = 1 where data_key = '{$tokenId}'";
        $d =  Mage::getSingleton('core/resource')->getConnection('core_write')->query($sql);

    }

    public function getTokenDefault($customer_id = false)
    {
        if(!$customer_id)
        {
            $customer_id = $this->_getQuoteCustomerId();
        }

        if(!$customer_id)
        {
            return false;
        }

        $table = $this->_getTable();

        $sql = "select * from {$table} where customer_id ='{$customer_id}' and `default_card` = 1 limit 1";
        $data_key =  Mage::getSingleton('core/resource')->getConnection('core_read')->fetchRow($sql);
        $date = '20'.substr($data_key['card_expiry_MMYY'],2,2).'-'.substr($data_key['card_expiry_MMYY'],0,2).'-28';
        $date_test = strtotime($date);

        //mage::log(__CLASS__ . " we hasve  some data " . print_r($data_key,1));
        if($date_test < strtotime("now"))
        {
            throw Mage::exception('Mage_Core', Mage::helper('chassistance')->__('The credit card on your account has expired.  Please log in via the website to update your account.'));
        }
        return $data_key['data_key'];
    }

    public function getTokenDataSinFromBs($token_id, $trnId)
    {

        $url = "https://www.beanstream.com/scripts/payment_profile.asp";
        $data = array();
        $std = new Collinsharper_Beanpro_Model_PaymentMethod;
        $data['serviceVersion'] = '1.0';
        $data['operationType'] = 'Q';
        $data['merchantId'] = $std->getParentConfig('merchant_id');
        $data['passCode'] = $std->getParentConfig('apikey');
        $data['customerCode'] = $token_id;
        $data['trnOrderNumber'] = $trnId;
        $url = $url.'?'.http_build_query($data);
        $ch = curl_init();
//		curl_setopt($ch, CURLOPT_POSTFIELDS,);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $qresult = curl_exec($ch);
        //parse_str($qresult,$return);
        $return = simplexml_load_string($qresult);

        if($this->getDebug() || $this->getTest())
        {
            mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r($url,1));
            mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r($data,1));
            mage::log(__CLASS__ . __FUNCTION__ . __LINE__ . print_r($return,1));
        }
        return $return->ref2;
    }

    public function getTokenData($token_id)
    {

        $table = $this->_getTable();

        $sql = "select * from {$table} where data_key ='{$token_id}' limit 1";
        $data_key =  Mage::getSingleton('core/resource')->getConnection('core_read')->fetchRow($sql);

        return $data_key;
    }

    public function deleteToken($tokenId)
    {
        $table = $this->_getTable();
        $sql = "select customer_id from {$table} where data_key = '{$tokenId}' limit 1";
        $customer_id =  Mage::getSingleton('core/resource')->getConnection('core_read')->fetchOne($sql);

        if($this->getSession()->getCustomerId() != $customer_id)
        {
            Mage::throwException(Mage::helper('chassistance')->__('Cannot delete Stored Card.'));
            return false;
        }

        $sql = "delete from {$table} where data_key = '{$tokenId}' limit 1";
        $d =  Mage::getSingleton('core/resource')->getConnection('core_write')->query($sql);
        return true;
    }

    public function recordTrxData($return)
    {
        $table = Mage::getSingleton('core/resource')->getTablename('collinsharper_beanpro_transaction');
        $sql = "insert into {$table} set `order_id` = '{$return->cardVerification->trnOrderNumber}', `guid` = '{$return->cardVerification->trnId}', `trx` = '{$return->cardVerification->customerCode}' , update_at = now()";
        //mage::log(__CLASS__ . " recording Tx bits " . $sql);
        Mage::getSingleton('core/resource')->getConnection('core_write')->query($sql);
    }

    public function isTest()
    {
        return Mage::getStoreConfig('payment/beanpro/test');
    }

    public function getProducts($order)
    {
        $test = '';
        $return = array();
        if($this->isTest())
        {
            $test = '{TEST}';
        }
        if(Mage::getStoreConfig('payment/beanpro/force_decline'))
        {
            $test = '{TESTD}';
        }
        $verification = 0;

        if($order->getBaseShippingAmount())
        {
            if(ceil($order->getBaseShippingAmount()) > 0)
            {
                $return[] = $this->_productLine($order->getBaseShippingAmount(),1,'SHP001',$order->getShippingDescription(), $test);
                $verification += $order->getBaseShippingAmount();
            }
        }

        if($order->getBaseDiscountAmount())
        {
            $return[] = $this->_productLine($order->getBaseDiscountAmount(),1,'DSC001',$order->getDiscountDescription(), $test);
            $verification += $order->getBaseDiscountAmount();
        }

        foreach($order->getFullTaxInfo() as $tid => $tax)
        {
            $return[] = $this->_productLine($tax['base_amount'],1,'TAX'.$tax['id'],$tax['id'], $test);
            $verification += $tax['base_amount'];
        }

        foreach($order->getItemsCollection(array(), true) as $item)
        {
            $return[] = $this->_productLine( $item->getBasePrice(),$item->getQtyOrdered(),$item->getSku(),$item->getName(), $test);
            $verification += $item->getBasePrice()*$item->getQtyOrdered();
        }

        $test = (float)$order->getBaseGrandTotal();
        $verification = (float)$verification;
        if(round($test-$verification) != 0)
        {
          //  mage::Log("we have a difference |" . $verification . "| vs |" . $order->getBaseGrandTotal()."|" . $test-$verification);
            if($verification > $order->getBaseGrandTotal())
            {
                throw new Exception('Failed to generate total for payment method.');
            }
            else
            {
                $return[] = $this->_productLine( $order->getBaseGrandTotal() - $verification,1,'CHG001','Processing Fees');
            }
        }

        return implode("|",$return);
    }

    public function saveReceipt($_d)
    {
        $session = $this->getSession();
        $order = Mage::getModel('sales/order');
        $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());

        $order->addStatusToHistory(
            $order->getStatus(),//continue setting current order status
            Mage::helper('beanpro')->__(' Beanstream Profiles CC Payment results, '.$_d)
        );
        $order->save();
    }


    public function getProfileData($quote_id, $profileId = false)
    {
        $table = Mage::getSingleton('core/resource')->getTablename(self::PROFILE_TABLE);

        $sql = "select * from {$table} where quote_id = {$quote_id}";
        if($profileId) {
            $sql .= " or profile_id = '{$profileId}' ";
        }
        return Mage::getSingleton('core/resource')->getConnection('core_read')->fetchRow($sql);
    }

    public function recordProfileData($profile, $paymentInfo)
    {
        $row = $this->getProfileData($paymentInfo->getQuote()->getId(), $profile->getId());
        $table = Mage::getSingleton('core/resource')->getTablename(self::PROFILE_TABLE);
        $sql = "insert into {$table} values (null, '{$paymentInfo->getCcOwner()}', '{$profile->getCustomerId()}', '{$profile->getId()}', '{$paymentInfo->getQuote()->getId()}', now(), now())";

        if($row) {
            $sql = "update {$table} set quote_id = '{$paymentInfo->getQuote()->getId()}' where entity_id = '{$row['entity_id']}'";
        }
        mage::log(__METHOD__ . __LINE__ . " we have SQL " . $sql);


        Mage::getSingleton('core/resource')->getConnection('core_write')->query($sql);

    }

}
