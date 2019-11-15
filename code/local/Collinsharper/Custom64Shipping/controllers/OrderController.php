<?php
require_once Mage::getModuleDir('controllers','TinyBrick_OrderEdit').DS.'OrderController.php';
class Collinsharper_Custom64Shipping_OrderController extends TinyBrick_OrderEdit_OrderController{
	public function editAction(){
		$order = $this->_initOrder();
		$orderArr = $order->getData();
		$shippingArr = array();
		$billingArr = $order->getBillingAddress()->getData();
		if(!$order->getIsVirtual()){
			$shippingArr = $order->getShippingAddress()->getData();
		}
		try{
			$preTotal = $order->getSubtotal();
			$edits = array();
			foreach($this->getRequest()->getParams() as $param){
				if(substr($param,0,1) == '{'){
					if($param = Zend_Json::decode($param)){
						$edits[] = $param;
					}
				}
			}
			$msgs = array();
			Mage::helper('orderedit/reconcile')->initializeCreditmemoData($order);
			$changes = array();
			foreach($edits as $edit){
				if($edit['type']){
					$model = Mage::getModel('orderedit/edit_updater_type_' . $edit['type']);
					if(!$changes[] = $model->edit($order,$edit)){
						$msgs[] = "Error updating ".$edit['type'];
					}
				}
			}
			if($order->getNewShippingAmount()){
				$subtotal = $order->getSubtotal();
				$shipping = $order->getNewShippingAmount();
				$giftcard = $orderArr['gift_cards_amount'];
				$tax =(($subtotal+$shipping-$giftcard)*0.0667);
				$baseGrandTotal = $subtotal+$shipping-$giftcard;
				$grandTotal =(($subtotal+$shipping-$giftcard)+$tax);
				foreach($order->getAllAddresses() as $address){
					$address->setTaxAmount($tax);
					$address->setBaseGrandTotal($baseGrandTotal);
					$address->setGrandTotal($grandTotal);
					$address->save();
				}
				$order->setShippingAmount($shipping);
				$order->setBaseGrandTotal($baseGrandTotal);
				$order->setGrandTotal($grandTotal);
				$order->setTaxAmount($tax);
				$order->save();
				Mage::dispatchEvent('orderedit_edit',array('order'=>$order));
                $this->_logChanges($order,$this->getRequest()->getParam('comment'),$this->getRequest()->getParam('admin_user'),$changes);
				echo "Order updated successfully. The page will now refresh.";
			}
		}catch(Exception $e){
			echo $e->getMessage();
			Mage::logException($e);
		}
		return $this;
	}
}
