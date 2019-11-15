<?php

class Collinsharper_FraudBlock_Helper_Data extends Mage_Core_Helper_Abstract
{

    const SECONDS = 60;
    const IP_FIELD = 'ip';
    const CUSTOMER_ID_FIELD = 'customer_id';
    const EMAIL_ID_FIELD = 'email';
    const CC_HASH_A = 'cc_hash_a';
    const CC_HASH_B = 'cc_hash_b';
    const RECORD_FRAUD_BLOCK = 'record_fraud_block';
    const RECORD_FRAUD_BLOCK_TIME = 'record_fraud_block_time';
    const SALT = 'chhash99';


    private function getCoreSession()
    {
        return Mage::getSingleton('core/session');
    }

    private function getQuoteId()
    {
        return Mage::getSingleton('checkout/session')->getQuoteId();
    }

    private function getQuoteTotal()
    {
        if(Mage::getSingleton('checkout/session')->getQuote()) {
            return round(Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal(),2);
        }

        return false;
    }

    private function getOrderId()
    {
        if(Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
           return  Mage::getSingleton('checkout/session')->getLastRealOrderId();
        }

        if(Mage::getSingleton('checkout/session')->getQuote() &&
            Mage::getSingleton('checkout/session')->getQuote()->getReservedOrderId()) {
            return Mage::getSingleton('checkout/session')->getQuote()->getReservedOrderId();
        }

        return false;
    }

    public function setBrowserFingerPrint($fingerPrint)
    {
        if($fingerPrint && !$this->getBrowserFingerPrint()) {
                $this->getCoreSession()->setBrowserFingerPrint($fingerPrint);
        }
    }

    public function getBrowserFingerPrint()
    {
        return $this->getCoreSession()->getBrowserFingerPrint();
    }

    public function log($val, $force = false)
    {
        if($force || Mage::getStoreConfig('sales/fraud_block/debug_logging')) {
            Mage::log($val, null, 'ch_fraud.log');
        }
    }

    private function _getIsStillTimeBlocked($val)
    {
        return (time() - $val) < $this->_getBlockTime() ;
    }

    private function _isFraudBlockType($which)
    {
        return true == preg_match("/{$which}/", Mage::getStoreConfig('sales/fraud_block/blocking_type'));
    }

    public function getCustomerId()
    {
        return Mage::getSingleton('customer/session')->getId();
    }

    public function getUserEmail()
    {
        // TODO we could traverse the post object for any thing named "*email*" that matches a XX@XX.X
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            return $customer->getEmail();
        }

        if(isset($_POST['email'])) {
            return $_POST['email'];
        }

        if(isset($_POST['billing'])) {
            return $_POST['billing']['email'];
        }

        return false;
    }


    private function _getBlockTime()
    {
        return (float) (self::SECONDS * Mage::getStoreConfig('sales/fraud_block/block_time'));
    }

    private function _ExceedsFraudCount($val)
    {
        return $val > Mage::getStoreConfig('sales/fraud_block/max_failed_attempts');
    }

    public function getFraudRule()
    {
        $is_fraud = false;
        if ($this->_isFraudBlockType(Collinsharper_FraudBlock_Model_Source_Blocktype::BLOCK_SESSION)) {

            $this->log(__METHOD__ . " fraud counts " . (isset($_SESSION['fraud_count']) ? $_SESSION['fraud_count'] : 0));

            if (!empty($_SESSION['fraud_count'])
                && $this->_ExceedsFraudCount($_SESSION['fraud_count'])
                && $this->_getIsStillTimeBlocked($_SESSION['last_fraud_at'])) {

                $is_fraud = Collinsharper_FraudBlock_Model_Source_Blocktype::BLOCK_SESSION;
            }

        }

        if ($this->_isFraudBlockType(Collinsharper_FraudBlock_Model_Source_Blocktype::BLOCK_IP)) {

            $fraud = $this->getFraudRecordByIp();

            if (!empty($fraud) &&
                $this->_ExceedsFraudCount($fraud->getFailedAttemptsCount())
                && $this->_getIsStillTimeBlocked($fraud->getLastAttemptAt())) {
                $this->log(__METHOD__ . __LINE__ ."  fraud by ip");

                $is_fraud  = Collinsharper_FraudBlock_Model_Source_Blocktype::BLOCK_IP;

            }
        }

        if ($this->_isFraudBlockType(Collinsharper_FraudBlock_Model_Source_Blocktype::BLOCK_DATA) && $this->_testAgainstRecords()) {
            $this->log(__METHOD__ . __LINE__ ."  fraud by data");

            $is_fraud = Collinsharper_FraudBlock_Model_Source_Blocktype::BLOCK_DATA;
        }
        return $is_fraud;
    }

    public function isFraud($is_fraud = false)
    {

        // we didnt get a rule?
        if($is_fraud === false) {
            $is_fraud = $this->getFraudRule();
        }

        // done in post observer.
        if($is_fraud !== false) {
            $this->log(__METHOD__ . __LINE__ . " IS fraud " . print_r($is_fraud, 1), true);
          //  $this->recordFraudBlockHit($is_fraud);
        }

        $this->log(__METHOD__ . __LINE__);
        return $is_fraud !== false;

    }

    public function recordFraudBlockHit($reason = false, $wasFailure = false)
    {
        $tracking = Mage::getModel('fraudblock/chfraudtrack');
        $tracking->setIp($this->getIpAddress());
        $tracking->setCustomerId($this->getCustomerId());
        $tracking->setEmail($this->getUserEmail());
        $tracking->setWasFailure($wasFailure != false ? 1 : 0);
        $tracking->setBrowserHash($this->getBrowserFingerPrint());
        $tracking->setBlockedReason($reason);
        $tracking->setOrderId($this->getOrderId());
        $tracking->setGrandTotal($this->getQuoteTotal());
        $tracking->setQuoteId($this->getQuoteId());

        if($reason == Collinsharper_FraudBlock_Model_Source_Blocktype::BLOCK_DATA) {
            $tracking->setBlockedReasonRuleId($_SESSION[self::RECORD_FRAUD_BLOCK]);
        }

        if($wasFailure && Mage::getStoreConfig('sales/fraud_block/hash_cards') &&
            isset($_POST['payment']) && isset($_POST['payment']['cc_number'])) {

            $hash = $this->getCcHash();
            $tracking->setCcHash($hash);

        }

        $tracking->save();
    }

    private function getCcHash()
    {
        if(isset($_POST['payment']) && isset($_POST['payment']['cc_number'])) {
                return hash('sha256', $_POST['payment']['cc_number'] . self::SALT);
        }
        return false;
    }

    private function _getFraudBanRecords($orFields, $orFilters)
    {
        $fraud = Mage::getModel('fraudblock/fraudban')->getCollection();
           return $fraud->addFilter('banned', 1)
            ->addFieldToFilter($orFields, $orFilters)
        ;
    }

    private function _isRecentRecordBlockedFraud()
    {
        return isset($_SESSION[self::RECORD_FRAUD_BLOCK]) && $_SESSION[self::RECORD_FRAUD_BLOCK] !== false
            && $this->_getIsStillTimeBlocked($_SESSION[self::RECORD_FRAUD_BLOCK_TIME]) ? $_SESSION[self::RECORD_FRAUD_BLOCK] : false;
    }

    private function _testAgainstRecords()
    {
        if($this->_isRecentRecordBlockedFraud() !== false) {
            return $this->_isRecentRecordBlockedFraud();
        }

        // TODO: we need more facilities to test all fields in the table and to record the data as well.
        /*
         *  ->addFieldToFilter(
            array('to_date', 'to_date'),
            array(array('gteq' => $now), array('nunull' => 'null')))
        ->addFieldToFilter(
            array('from_date', 'from_date'),
            array(array('lteq' => $now), array('null' => 'null')))
         */
        $orFields = array(self::IP_FIELD);
        $orFilters[] = array('eq' => $this->getIpAddress());
        if($this->getCustomerId()) {
            $orFields[] = self::CUSTOMER_ID_FIELD;
            $orFilters[] = array('eq' => $this->getCustomerId());

        }

        $email = $this->getUserEmail();

        if($email) {
            $orFields[] = self::EMAIL_ID_FIELD;
            $orFilters[] = array('eq' => $email);
        }

        if(Mage::getStoreConfig('sales/fraud_block/hash_cards')) {
            $hash = $this->getCcHash();
            if($hash) {
                $orFields[] = self::CC_HASH_A;
                $orFilters[] = array('eq' => $hash);
                $orFields[] = self::CC_HASH_B;
                $orFilters[] = array('eq' => $hash);
            }

        }
	//if(enable_browser_fingerprint
if(Mage::getStoreConfig('sales/fraud_block/enable_browser_fingerprint')) {
            $hash = $this->getBrowserFingerPrint();
            if($hash) {
                $orFields[] = 'browser_hash';
                $orFilters[] = array('eq' => $hash);
            }

        }



        $fraud = $this->_getFraudBanRecords($orFields, $orFilters);

      //  $this->log(__METHOD__ . __LINE__ . " we have FILTERS " . print_r($orFilters, 1));
        $this->log(__METHOD__ . __LINE__ . " we have SQL " . $fraud->getSelect()->__toString());

        // TODO : We need some logging to trace why and when a customer was banned
        // TODO : might need a nice way to wipe a customers fraud status? incase of by accident?
            if( $fraud->count() > 0 ) {
                $_SESSION[self::RECORD_FRAUD_BLOCK] = $fraud->getFirstItem()->getId();
                $_SESSION[self::RECORD_FRAUD_BLOCK_TIME] = time();
                return $_SESSION[self::RECORD_FRAUD_BLOCK];
            }
    }

    private function getIpAddress($long = true)
    {
        return Mage::helper('core/http')->getRemoteAddr($long);
    }

    private function getFraudRecordByIp()
    {
        return Mage::getModel('fraudblock/fraud')->getCollection()->addFilter(self::IP_FIELD, $this->getIpAddress())->getFirstItem();;
    }

    public function recordFraudAttempt()
    {

        if ($this->_isFraudBlockType(Collinsharper_FraudBlock_Model_Source_Blocktype::BLOCK_SESSION)) {
            if (empty($_SESSION['fraud_count'])) {
                $_SESSION['fraud_count'] = 1;
            } else {
                $_SESSION['fraud_count']++;
            }
            $_SESSION['last_fraud_at'] = time();
        }

        if ($this->_isFraudBlockType(Collinsharper_FraudBlock_Model_Source_Blocktype::BLOCK_IP)) {

            $fraud = $this->getFraudRecordByIp();

            if (empty($fraud)) {

                $fraud = Mage::getModel('fraudblock/fraud');

                $fraud->setIp($this->getIpAddress());

                $fraud->setFailedAttemptsCount(1);

            } else {

                $fraud->setFailedAttemptsCount($fraud->getFailedAttemptsCount()+1);

            }

            $fraud->setLastAttemptAt(time());

            $fraud->save();

        }


    }

}
