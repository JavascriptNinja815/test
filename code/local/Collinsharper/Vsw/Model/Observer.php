<?php
class Collinsharper_Vsw_Model_Observer
{
    public function setVswFeatureLinkOnOrderSuccessPageView($observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        $orderValue = "";
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        } else {
            $order = Mage::getModel('sales/order')->load($orderIds[0]);
            $orderIncrementId = $order->getIncrementId();
            $orderValue = number_format($order->getGrandTotal(),2,".","");
        }
        $block = Mage::app()->getFrontController()->getAction()->getLayout()->getBlock('vsw_feature_link');
        if ($block) {
            $block->setOrderIds($orderIncrementId);
            $block->setOrderValue($orderValue);
        }
    }
}
