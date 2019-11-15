<?php 
if(class_exists('TinyBrick_OrderEdit_Helper_Reconcile')){
    class Collinsharper_Custom64Shipping_Helper_Data extends TinyBrick_OrderEdit_Helper_Reconcile{
        public function addShippingAdjustmentToOrder($preData,$order){
            $subtotal = $order->getSubtotal();
            $shipping = $order->getNewShippingAmount();
            $giftcard = $preData['gift_cards_amount'];
            $tax = (($subtotal+$shipping-$giftcard)*0.0667);
            $baseGrandTotal = $subtotal+$shipping-$giftcard;
            $grandTotal = (($subtotal+$shipping-$giftcard)+$tax);
            foreach($order->getAllAddresses() as $address){
                $address->setBaseGrandTotal($baseGrandTotal)->setGrandTotal($grandTotal);
                $address->setTaxAmount($tax);
                $address->save();
            }
            $order->setShippingAmount($shipping);
            $order->setTaxAmount($tax);
            $order->save();
            return $this;
        }
        public function reconcile($difference,$order,$needInvoice = true){}
    }
}else{
    class Collinsharper_Custom64Shipping_Helper_Data extends Mage_Core_Helper_Abstract{

    }
}