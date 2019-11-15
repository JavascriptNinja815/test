<?php

/**
 * Class added by Collins Harper for reconciling differences in qty/subtotal after editing an order.
 */
class TinyBrick_OrderEdit_Helper_Reconcile extends Mage_Core_Helper_Abstract
{

    public function addShippingAdjustmentToOrder($preData, $order)
    {
        $originalShippingAmount = isset($preData['shipping_amount']) ? $preData['shipping_amount'] : 0;
        $newShippingAmount = $order->getNewShippingAmount();
        $adjustmentAmount = $newShippingAmount - $originalShippingAmount;
        $currency = $order->getBaseCurrency();

        $item = Mage::getModel('sales/order_item')
            ->setStoreId($order->getStore()->getStoreId())
            ->setQuoteItemId(null)
            ->setProductId(null)
            ->setProductType(Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL)
            ->setSku(null)
            ->setQtyOrdered(1)
            ->setName("Shipping Adjustment: {$currency->format($newShippingAmount, array(), false)} - {$currency->format($originalShippingAmount, array(), false)} = {$currency->format($adjustmentAmount, array(), false)}")
            ->setPrice($adjustmentAmount)
            ->setBasePrice($adjustmentAmount)
            ->setOriginalPrice($adjustmentAmount)
            ->setRowTotal($adjustmentAmount)
            ->setOrder($order)
            ->save();

        return $this;
    }

    /**
     * Reconciles items and totals for $order.
     *
     * @param $difference, $order
     * @return $this
     */
    public function reconcile($difference, $order)
    {
        // put the customer object on the order so Authorizenetcimsoap can use it
        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        $order->setCustomer($customer);

        $transaction = Mage::getModel('core/resource_transaction');

        $invoiced = $this->_invoice($transaction, $order);
        $memoed = $this->_creditmemo($difference, $transaction, $order);

        if ($invoiced || $memoed) {
            $order->save();
            $transaction->addObject($order)->save();
        }

        return $this;
    }

    /**
     * Creates an invoice for $order, if necessary.
     * This will both reconcile items and charge the customer for any difference.
     *
     * @param $transaction, $order
     * @return true if an invoice has been created and added to the transaction, else false
     */
    protected function _invoice($transaction, $order)
    {
        // check for creditmemos; if the order has any, then the no additional charge can be added
        $creditmemos = Mage::getResourceModel('sales/order_creditmemo_collection')
            ->setOrderFilter($order);
        if ($creditmemos->getSize()) {
            throw new Exception($this->__('This order has already been edited for discount/partial refund; adding charges is no longer allowed.'));
        }

        $service = Mage::getModel('sales/service_order', $order);
        $invoice = $service->prepareInvoice();

        if ($invoice->getSubtotal() <= 0 && !count($invoice->getAllItems())) {
            return false;
        }

        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE)
            ->register();

        if ($invoice->getSubtotal() < 0) {
            $invoice->setBaseTotalRefunded($invoice->getSubtotal());
        }

        $invoice->save();
        $transaction->addObject($invoice);
        return true;
    }

    /**
     * Issues a refund for $order, if necessary.
     *
     * @param $difference, $transaction, $order
     * @return true if a creditmemo has been created an added to the transaction, else false
     */
    protected function _creditmemo($difference, $transaction, $order)
    {
        // refund, if necessary
        if (bccomp($difference, 0) >= 0) {
            return false;
        }

        $service = Mage::getModel('sales/service_order', $order);
        $creditmemo = $service->prepareCreditmemo($order->getOrdereditCreditmemoData());
        // force creditmemo total to $difference to account for changes in shipping cost
        // (simply adding up the items isn't good enough)
        // set shipping amount to 0 to avoid automatically refunding shipping
        $creditmemo->setShippingAmount(0)
            ->setBaseShippingAmount(0)
            ->setBaseSubtotal($difference)
            ->setSubtotal($difference)
            ->setBaseGrandTotal($difference)
            ->setGrandTotal($difference);

        if ($order->getNewShippingAmount()) {
            $creditmemo->addComment($this->__('Includes shipping price adjustment.'));
        }

        $creditmemo->register();
        $creditmemo->save();
        $transaction->addObject($creditmemo);

        return true;
    }

    /**
     * Creates a creditmemo data array with qts set to 0 for all item IDs in the order.
     * Example:
     * array(
     *     'qtys' : array(
     *         '17004' => 0
     *     )
     * )
     *
     * @param $order
     * @return $this
     */
    public function initializeCreditmemoData($order)
    {
        $creditmemoData = array('qtys' => array());

        foreach ($order->getAllItems() as $item) {
            $creditmemoData['qtys'][$item->getItemId()] = 0.0;
        }

        $order->setOrdereditCreditmemoData($creditmemoData);

        return $this;
    }

}
