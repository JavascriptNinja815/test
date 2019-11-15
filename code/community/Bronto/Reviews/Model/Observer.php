<?php

/**
 * @package   Bronto\Reviews
 * @copyright 2011-2013 Bronto Software, Inc.
 */
class Bronto_Reviews_Model_Observer
{
    const NOTICE_IDENTIFER = 'bronto_reviews';

    // Helper
    protected $_helper;

    // Data Members
    protected $_contact;
    protected $_order;
    protected $_deliveryObject;
    protected $_deliveryRow;
    protected $_deliveryId;

    public function __construct()
    {
        /* @var Bronto_Reviews_Helper_Data $_helper */
        $this->_helper = Mage::helper(self::NOTICE_IDENTIFER);
    }

    /**
     * Set Contact Row Object to use
     *
     * @param Bronto_Api_Contact_Row $contact
     *
     * @return Bronto_Reviews_Model_Observer
     */
    public function setContact(Bronto_Api_Contact_Row $contact)
    {
        $this->_contact = $contact;

        return $this;
    }

    /**
     * Set Order to use
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return Bronto_Reviews_Model_Observer
     */
    public function setOrder(Mage_Sales_Model_Order $order)
    {
        $this->_order = $order;

        return $this;
    }

    /**
     * Get Order to use
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Set Delivery Object to use
     *
     * @param Bronto_Api_Delivery $deliveryObject
     *
     * @return Bronto_Reviews_Model_Observer
     */
    public function setDeliveryObject(Bronto_Api_Delivery $deliveryObject)
    {
        $this->_deliveryObject = $deliveryObject;

        return $this;
    }

    /**
     * Get Delivery Object to use
     *
     * @return boolean|Bronto_Api_Delivery
     */
    public function getDeliveryObject()
    {
        if (!$this->_deliveryObject) {
            try {
                // Retrieve Store's configured API Token
                $token = $this->_helper->getApiToken('store', $this->getOrder()->getStoreId());

                /* @var Bronto_Common_Model_Api $api */
                $api = $this->_helper->getApi($token, 'store', $this->getOrder()->getStoreId());

                /* @var Bronto_Api_Delivery $deliveryObject */
                $this->_deliveryObject = $api->getDeliveryObject();
            } catch (Exception $e) {
                $this->_helper->writeError('Bronto Failed creating apiObject:' . $e->getMessage());

                return false;
            }
        }

        return $this->_deliveryObject;
    }

    /**
     * Set Delivery Row Object to use
     *
     * @param Bronto_Api_Delivery_Row $deliveryRow
     *
     * @return Bronto_Reviews_Model_Observer
     */
    public function setDeliveryRow(Bronto_Api_Delivery_Row $deliveryRow)
    {
        $this->_deliveryRow = $deliveryRow;

        return $this;
    }

    /**
     * Get Delivery Row if exists, create if doesn't
     *
     * @return boolean
     */
    public function getDeliveryRow()
    {
        if (!$this->_deliveryRow) {
            try {
                $this->_deliveryRow = $this->getDeliveryObject()->createRow(array());
            } catch (Exception $e) {
                $this->_helper->writeError('Bronto Failed creating apiObject:' . $e->getMessage());

                return false;
            }
        }

        return $this->_deliveryRow;
    }

    /**
     * Set Delivery ID
     *
     * @param string $deliveryId
     *
     * @return Bronto_Reviews_Model_Observer
     */
    public function setDeliveryId($deliveryId)
    {
        $this->_deliveryId = $deliveryId;

        return $this;
    }

    /**
     * Get Delivery ID
     *
     * @return string
     */
    public function getDeliveryId()
    {
        return $this->_deliveryId;
    }

    /**
     * Observe saving of Order and determine if a Review Request should be sent
     * and then send
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Varien_Event_Observer
     */
    public function markOrderForReview(Varien_Event_Observer $observer)
    {
        if (!$this->_helper->isEnabled('store', Mage::app()->getStore()->getId())) {
            return $observer;
        }

        $this->setOrder($observer->getOrder())->process();

        return $observer;
    }

    /**
     * Process Order for Review Request
     */
    public function process()
    {
        /* @var $order Mage_Sales_Model_Order */
        $order = $this->getOrder();

        // Get Statuses
        $reviewStatus = $this->_helper->getReviewSendStatus('store', $order->getStoreId());
        $cancelStatus = $this->_helper->getReviewCancelStatus('store', $order->getStoreId());

        // If Original Increment ID is Null, proceed
        if (is_null($order->getOriginalIncrementId())) {
            // If current order status matches review send status, proceed
            if ($order->getStatus() == $reviewStatus) {
                $reviewQueue = Mage::getModel('bronto_reviews/queue')
                    ->load($order->getId());

                // If Queue Doesn't have Delivery ID, proceed
                if (is_null($reviewQueue->getDeliveryId())) {
                    $this->_makeDelivery();

                    // If Delivery Row sent correctly, save the ID
                    if ($this->getDeliveryId()) {
                        $reviewQueue->setDeliveryId($this->getDeliveryId())->save();
                    }
                }
            } elseif (in_array($order->getStatus(), $cancelStatus)) {
                $reviewQueue = Mage::getModel('bronto_reviews/queue')
                    ->load($order->getId());

                // If Queue has Delivery ID, cancel Delivery
                if (!is_null($reviewQueue->getDeliveryId())) {
                    $this->_cancelDelivery($reviewQueue->getDeliveryId());
                }
            }
        }
    }

    /**
     * Deletes the Delivery that was previously created
     *
     * @param $deliveryId
     */
    protected  function _cancelDelivery($deliveryId)
    {
        try {
            $delivery = $this->getDeliveryObject();
            $result = $delivery->update(array('id' => $deliveryId, 'status' => 'skipped'));
            if ($result->hasErrors()) {
                $errors = array();
                foreach ($result->getErrors() as $soapFault) {
                    $errors[] = $soapFault['code'] . ": " . $soapFault['message'];
                }
                $error = implode('<br />', $errors);

                Mage::throwException($error);
            }
        } catch (Exception $e) {
            $this->_helper->writeError('Failed Cancelling Delivery: ' . $e->getMessage());
        }
    }

    /**
     * Create Delivery With Order Details
     */
    protected function _makeDelivery()
    {
        $helper = $this->_helper;
        try {

            $order = $this->getOrder();
            $storeId = $order->getStoreId();
            $sender = array(
                'name' => $helper->getReviewSenderName('store', $storeId),
                'email' => $helper->getReviewSenderEmail('store', $storeId)
            );

            $message = new Bronto_Api_Message_Row();
            $message->id = $helper->getReviewSendMessage('store', $storeId);

            $helper->writeDebug(' Creating review delivery...');
            $message = Mage::getModel('bronto_reviews/message')
                ->setDesignConfig(array('area' => 'frontend', 'store' => $storeId))
                ->setSalesRule($helper->getDefaultRule('store', $storeId))
                ->setProductRecommendation($helper->getDefaultRecommendation('store', $storeId))
                ->setTemplateSendType('marketing')
                ->setOrderId($order->getId())
                ->sendTransactional(
                    $message,
                    $sender,
                    array($order->getCustomerEmail()),
                    array($order->getCustomerName()),
                    array('order' => $order),
                    $storeId
                );
            if ($message->getSentSuccess()) {
                $helper->writeDebug(' Successfully created delivery.');
            } else {
                $helper->writeError(' Failed to sent the message.');
            }
        } catch (Exception $e) {
            $helper->writeError('Bronto Failed creating apiObject:' . $e->getMessage());
        }
    }
}
