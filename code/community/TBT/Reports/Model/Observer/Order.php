<?php

class TBT_Reports_Model_Observer_Order extends Varien_Object
{
    /**
     * Called by sales_order_place_after event in Magento.
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function onOrderPlaceAfter($observer)
    {
        $event = $observer->getEvent();
        $order = $event->getOrder();

        try {
            Mage::getSingleton('index/indexer')->processEntityAction(
                $order,
                TBT_Rewards_Model_Sales_Order::ENTITY,
                TBT_Reports_Model_Indexer_Order::EVENT_TYPE_ORDER_PLACE_AFTER
            );
        } catch (Exception $exc) {
            Mage::logException($exc);
        }

        return $this;
    }
}