<?php
class TinyBrick_OrderEdit_OrderController extends Mage_Adminhtml_Controller_Action{
	public function editAction(){
		$order = $this->_initOrder();
		$orderArr = $order->getData();
		if($order->getNewShippingAmount()){
			$subtotal = $order->getSubtotal();
			$shipping = $order->getNewShippingAmount();
			$giftcard = $orderArr['gift_cards_amount'];
			$tax =(($subtotal+$shipping-$giftcard)*0.0667);
			$baseGrandTotal = $subtotal+$shipping-$giftcard;
			$grandTotal =(($subtotal+$shipping-$giftcard)+$tax);
			foreach($order->getAllAddresses() as $address){
				$address->setBaseGrandTotal($baseGrandTotal)->setGrandTotal($grandTotal);
				$address->save();
			}
			$order->setShippingAmount($shipping);
			$order->setTaxAmount($tax);
			$order->save();
		}
		return $this;
	}
	protected function _initOrder(){
		$id = $this->getRequest()->getParam('order_id');
		$order = Mage::getModel('orderedit/order')->load($id);
		if(!$order->getId()){
			$this->_getSession()->addError($this->__('This order no longer exists.'));
			$this->_redirect('*/*/');
			$this->setFlag('',self::FLAG_NO_DISPATCH,true);
			return false;
		}
		Mage::register('sales_order',$order);
		Mage::register('current_order',$order);
		return $order;
	}
	protected function _orderRollBack($order,$orderArray,$billingArray,$shippingArray){
		$order->setData($orderArray)->save();
		$order->getBillingAddress()->setData($billingArray)->save();
		if($shippingArray){
			$order->getShippingAddress()->setData($shippingArray)->save();
		}
		$order->collectTotals()->save();
	}
	protected function _logChanges($order,$comment,$user,$array = array()){
		$logComment = $user . " made changes to this order. <br /><br />";
		foreach($array as $change){
			if($change != 1){
				$logComment .= $change;
			}
		}
		$logComment .= "<br />User comment: " . $comment;
		$status = $order->getStatus();
		$notify = 0;
		$order->addStatusToHistory($status,$logComment,$notify);
		$order->save();
	}
	public function updateCommentAction(){
		if($order = $this->_initOrder()){
			echo $this->getLayout()->createBlock('adminhtml/sales_order_view_history')->setTemplate('sales/order/view/history.phtml')->toHtml();
		}
	}
	public function recalcAction(){
		echo $this->getLayout()->createBlock('orderedit/adminhtml_sales_order_shipping_update')->setTemplate('sales/order/view/tab/shipping-form.phtml')->toHtml();
	}
	public function newItemAction(){
		echo $this->getLayout()->createBlock('orderedit/adminhtml_sales_order_view_items_add')->setTemplate('sales/order/view/items/add.phtml')->toHtml();
	}
	public function addFromGridAction(){
		echo $this->getLayout()->createBlock('orderedit/adminhtml_sales_order_view_items_add')->setTemplate('sales/order/view/items/add_from_grid.phtml')->toHtml();
	}
	public function getQtyAndDescAction(){
		$sku = $this->getRequest()->getParam('sku');
		$product = Mage::getModel('catalog/product')->getCollection()
			->addAttributeToSelect('*')
			->addAttributeToFilter('sku',$sku)
			->getFirstItem();
		if($product->isConfigurable()){
			$allProducts = $product->getTypeInstance(true)->getUsedProducts(null,$product);
			$simpleskus = "<p style='color:red;'>Configurable Product! Select a simple product from dropdown!</p>";
			$simpleskus .= "<select class='n-item-simplesku'>";
			foreach($allProducts as $subproduct){
				$skunumber = $subproduct->getSku();
				$simpleskus .= "<option value='" . $skunumber . "'>" . $skunumber . "</option>";
			}
			$simpleskus .= "</select>";
		}
		$return = array();
		$return['simpleskus'] = $simpleskus;
		$return['name'] = $product->getName();
		if($product->getSpecialPrice()){
			$return['price'] = round($product->getSpecialPrice(),2);
		}else{
			$return['price'] = round($product->getPrice(),2);
		}
		if($product->getManageStock()){
			$qty = $product->getQty();
		}else{
			$qty = 10;
		}
		$select = "<select class='n-item-qty'>";
		$x = 1;
		while($x <= $qty){
			$select .= "<option value='" . $x . "'>" . $x . "</option>";
			$x++;
		}
		$select .= "</select>";
		$return['select'] = $select;
		echo Zend_Json::encode($return);
	}
	public function gridAction(){
		$this->loadLayout();
		$this->renderLayout();
	}
	public function jsonAction(){
		$storeId =(int) $this->getRequest()->getParam('store',0);
		$store = Mage::app()->getStore($storeId);
		$start = $this->getRequest()->getParam('page');
		$limit = $this->getRequest()->getParam('rows');
		$collection = Mage::getModel('catalog/product')->getCollection()
			->addAttributeToSelect('sku')
			->addAttributeToSelect('name')
			->joinField('qty','cataloginventory/stock_item','qty','product_id=entity_id','{{table}}.stock_id=1','left');

		if($store->getId()){
			$adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
			$collection->addStoreFilter($store);
			$collection->joinAttribute('name','catalog_product/name','entity_id',null,'inner',$adminStore);
			$collection->joinAttribute('price','catalog_product/price','entity_id',null,'left',$store->getId());
		}else{
			$collection->addAttributeToSelect('price');
		}
		$gridProduct = array();
		$i = 0;
		if($this->getRequest()->getParam('sku')!=''){
			$returnString='';
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku',$this->getRequest()->getParam('sku'));
			$gridProduct[] = array(
				'id' => $product->getId(),
				'name' => $product->getName(),
				'sku' => $this->getRequest()->getParam('sku'),
				'price' => number_format($product->getPrice(),2),
				'qty' => 1
			);
			$returnString = '{"total":1' . ',"rows":' .
			json_encode($gridProduct) . "}";
			echo $returnString;
		}elseif($this->getRequest()->getParam('id')!=''){
			$returnString='';
			$product = Mage::getModel('catalog/product')->load($this->getRequest()->getParam('id'));
			$gridProduct[] = array(
				'id' => $this->getRequest()->getParam('id'),
				'name' => $product->getName(),
				'sku' =>  $product->getSku(),
				'price' => number_format($product->getPrice(),2),
			'qty' => 1
			);
			$returnString = '{"total":1' . ',"rows":' .
			json_encode($gridProduct) . "}";
			echo $returnString;
		}else{
			foreach($collection as $product){
				if($i < count($collection)){
					$attributeSetModel = Mage::getModel('eav/entity_attribute_set')->load($product->attribute_set_id);
					$product->name = str_replace("'","",$product->name);
					$gridProduct[] = array(
						'id' => $product->entity_id,
						'name' => $product->name,
						'sku' => $product->sku,
						'price' => number_format($product->price,2),
						'qty' => 1
					);
				}
				$i++;
			}
			$returnString = '{"total":' .($collection->getSize()) . ',"rows":' .
			json_encode($gridProduct) . "}";
			echo $returnString;
		}
	}
	public function setstatusAction(){
		$this->loadLayout();
		$model = Mage::getModel('catalog/product');
		$model->load($this->getRequest()->getParam('id'));
		if($this->getRequest()->getParam('status') != null){
			$model->status = $this->getRequest()->getParam('status');
		}
		if($this->getRequest()->getParam('name') != null){
			$model->name = $this->getRequest()->getParam('name');
		}
		if($this->getRequest()->getParam('price') != null){
			$model->price = $this->getRequest()->getParam('price');
		}
		if($this->getRequest()->getParam('qty') != null){
			$productId = $this->getRequest()->getParam('id');
			$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
			$stock->setQty($this->getRequest()->getParam('qty'));
		}
		if($this->getRequest()->getParam('visibility') != null){
			$model->visibility = $this->getRequest()->getParam('visibility');
		}
		if($this->getRequest()->getParam('status') != null){
			$model->status = $this->getRequest()->getParam('status');
		}
		try{
			$model->save();
			$stock->save();
		} catch(Exception $e){
		  
		}
	}
	private function _evalString($string){
		$flag = preg_replace("'","\'",$string);
		$flag = preg_replace('"',"\&#34;",$string);
		return $string;
	}
}
