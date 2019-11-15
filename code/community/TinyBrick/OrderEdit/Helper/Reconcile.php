<?php
class TinyBrick_OrderEdit_Helper_Reconcile extends Mage_Core_Helper_Abstract{
    public function addShippingAdjustmentToOrder($preData,$order){
        $newShippingAmount = $order->getNewShippingAmount();
        $order->setShippingAmount($newShippingAmount);
        $order->save();
        return $this;
    }
    public function reconcile($difference,$order){
        $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
        $order->setCustomer($customer);
        $transaction = Mage::getModel('core/resource_transaction');
        $invoiced = $this->_invoice($transaction,$order);
        $memoed = $this->_creditmemo($difference,$transaction,$order);
        if ($invoiced || $memoed){
            $order->save();
            $transaction->addObject($order)->save();
        }
        return $this;
    }
    protected function _invoice($transaction,$order){
        $creditmemos = Mage::getResourceModel('sales/order_creditmemo_collection')->setOrderFilter($order);
        if($creditmemos->getSize()){
            throw new Exception($this->__('This order has already been edited for discount/partial refund; adding charges is no longer allowed.'));
        }
        $service = Mage::getModel('sales/service_order',$order);
        $invoice = $service->prepareInvoice();
        if($invoice->getSubtotal() <= 0 && !count($invoice->getAllItems())){
            return false;
        }
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE)->register();
        if($invoice->getSubtotal() < 0){
            $invoice->setBaseTotalRefunded($invoice->getSubtotal());
        }
        $invoice->save();
        $transaction->addObject($invoice);
        return true;
    }
    protected function _creditmemo($difference,$transaction,$order){
        if(bccomp($difference,0) >= 0){
            return false;
        }
        $service = Mage::getModel('sales/service_order',$order);
        $creditmemo = $service->prepareCreditmemo($order->getOrdereditCreditmemoData());
        $creditmemo->setShippingAmount(0)->setBaseShippingAmount(0)->setBaseSubtotal($difference)->setSubtotal($difference)->setBaseGrandTotal($difference)->setGrandTotal($difference);
        if ($order->getNewShippingAmount()){
            $creditmemo->addComment($this->__('Includes shipping price adjustment.'));
        }
        $creditmemo->register();
        $creditmemo->save();
        $transaction->addObject($creditmemo);
        return true;
    }
    public function initializeCreditmemoData($order){
        $creditmemoData = array('qtys' => array());
        foreach($order->getAllItems() as $item){
            $creditmemoData['qtys'][$item->getItemId()] = 0.0;
        }
        $order->setOrdereditCreditmemoData($creditmemoData);
        return $this;
    }
}