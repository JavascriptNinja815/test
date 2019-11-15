<?php

class Bronto_Reviews_Model_Message extends Bronto_Common_Model_Email_Template
{
    protected $_helper = 'bronto_reviews';

    protected $_apiLogFile = 'bronto_reviews_api.log';

    /**
     * @see parent
     */
    protected function _emailClass()
    {
        return 'bronto_reviews/message';
    }

    /**
     * @see parent
     */
    protected function _startTime($storeId)
    {
        $helper = Mage::helper($this->_helper);
        $sendPeriod = $helper->getReviewSendPeriod('store', $storeId);
        return date('c', strtotime('+' . abs($sendPeriod) . ' days'));
    }

    /**
     * @see parent
     */
    protected function _additionalFields($delivery, $variables)
    {
        $order = $variables['order'];
        $storeId = $order->getStoreId();
        $helper = Mage::helper('bronto_common/product');
        $reviewHelper = Mage::helper('bronto_reviews');
        $urlSuffix = ltrim($reviewHelper->getProductUrlSuffix('store', $storeId), '/');

        $index = 1;
        foreach ($order->getAllItems() as $item) {
          if (!$item->getParentItem()) {
                $product = Mage::getModel('catalog/product')
                    ->setStoreId($storeId)
                    ->load($item->getProductId());

                $productUrl = $helper->getProductAttribute($product, 'url', $storeId) . $urlSuffix;
                $reviewUrl = $reviewHelper->getReviewsUrl($product, $storeId) . $urlSuffix;

                $delivery->setField('reviewUrl_' . $index, $reviewUrl, 'html');
                $delivery->setField('productUrl_' . $index, $productUrl, 'html');
                $index++;
            }
        }
    }

    /**
     * @see parent
     */
    protected function _additionalData()
    {
        return array('order_id' => $this->getOrderId());
    }

    /**
     * @see parent
     */
    protected function _afterSend($success, $error = null, Bronto_Api_Delivery_Row $delivery = null)
    {
        $helper = Mage::helper($this->_helper);
        if (!is_null($delivery)) {
            if ($success) {
                $review = Mage::getModel('bronto_reviews/queue')
                    ->load($this->getParams()->getOrderId())
                    ->setDeliveryId($delivery->id);
                if (!is_null($review->getDeliveryId())) {
                    $review->save();
                }
            }
            $status = $success ? 'Successful' : 'Failed';

            $helper->writeVerboseDebug("===== $status Reviews Delivery =====", $this->_apiLogFile);
            $helper->writeVerboseDebug(var_export($delivery->getApi()->getLastRequest(), true), $this->_apiLogFile);
            $helper->writeVerboseDebug(var_export($delivery->getApi()->getLastResponse(), true), $this->_apiLogFile);
        }
    }
}
