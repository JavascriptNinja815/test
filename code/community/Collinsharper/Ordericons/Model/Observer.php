<?php

class Collinsharper_Ordericons_Model_Observer

{

   public function beforeBlockToHtml(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            $ordererTags = mage::getModel('chordericons/Source')->getOptionsList();

            $block->addColumnAfter(
                "ch_order_icons",
                array(
                    'header' => Mage::helper('chordericons')->__('Order Icons'),
                    'index'  => "ch_order_icons",
                    'type' => 'options',
                    'options' => $ordererTags,
                    'renderer' => 'Collinsharper_Ordericons_Block_Adminhtml_Renderer_Ordericons',
                    'filter_condition_callback' => array($this, '_applyMyFilter'),
                ),
                'the_first_column'
            );

        }
    }

    public function _applyMyFilter($collection, $column)
    {

        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $value = (int) $value;
        $tablename = Mage::getSingleton('core/resource')->getTableName('ch_sales_flat_order_icons');
        $collection->getSelect()->joinLeft(array('chicon'=> $tablename), 'chicon.order_id=main_table.entity_id',array('icon_id'=>'icon_id'));
        $collection->addFieldToFilter('chicon.icon_id', array('eq' => $value));

    }


    public function beforeCollectionLoad(Varien_Event_Observer $observer)
    {
	mage::log(__METHOD__ .__LINE__ );

        $collection = $observer->getOrderGridCollection();

        if (!isset($collection)) {
            return;
        }

	mage::log(__METHOD__ .__LINE__ );
        if ($collection instanceof Mage_Sales_Model_Resource_Order_Grid_Collection) {

            $tablename = Mage::getSingleton('core/resource')->getTableName('ch_sales_flat_order_icons');
            // TODO we could skip another collection loading if we would have to send back image|name|id... seems messy..
            // $iconTablename = Mage::getSingleton('core/resource')->getTableName('ch_sales_flat_order_icons');
            //  $collection->getSelect()->columns(array("ch_order_icons" => new Zend_Db_Expr("(select group_concat(concat(image, '|', name) SEPARATOR  ';') from {$tablename} x, {$iconTablename} e  where e.icon_id = x.icon_id and x.order_id = main_table.entity_id )")));
            $collection->getSelect()->columns(array("ch_order_icons" => new Zend_Db_Expr("(select group_concat(icon_id) from {$tablename} x where x.order_id = main_table.entity_id )")));
        }
	//mage::log($collection->getSelect()->__toString());
    }
    
    public function salesOrderLoadAfter(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $resource = Mage::getSingleton('core/resource');
	$readConnection = $resource->getConnection('core_read');
	$tablename = Mage::getSingleton('core/resource')->getTableName('ch_sales_flat_order_icons');
	$query = "select group_concat(icon_id) as icon_id from {$tablename} x where x.order_id = ".$order->getId();
        $result = $readConnection->fetchAll($query);
        $iconIds = array();
        foreach ($result as $row) {
            $iconIds = $row['icon_id'];
        }
        $order->setData('ch_order_icons', $iconIds);
        return $this;
    }

    public function update_tags($event)
    {
        mage::log(__METHOD__ . __LINE__, null, 'observer.log');
		$order = $event->getEvent()->getOrder();
		$orderId = $order->getId();
			/** wayne fixed **/
		$shippingMethod = $order->getShippingMethod();
		$orderIconsData = array();
		if($shippingMethod == 'storepickupmodule_pickup') {
				$quoteCards = Mage::helper('aw_giftcard/totals')->getQuoteGiftCards($order->getQuoteId());
			foreach ($quoteCards as $quoteCard) {
				if(substr($quoteCard->getCode(), 0, 2) == 'RV') {
					$retailId = $this->getIconIdByName('retail');
					if(!in_array($retailId, $orderIconsData)) {
						$orderIconsData[] = $retailId;
					}
				}
			}
		}
		foreach($order->getAllItems() as $item) {
			try {
			$sku = $item->getData('sku');
			if(substr($sku, 0, 7) == 'imprint') {
				//Add the imprint tag
				if(!in_array(318, $orderIconsData)) {
					$orderIconsData[] = 318;
				}
				}
			} catch (Exception $e) {
			mage::logException($e);
			}
		}
        Mage::helper('chordericons')->setOrderIcons($orderId, $orderIconsData);
        /** end wayne fixed **/
   }
   
   public function getIconIdByName($name)
    {
        $iconModel = Mage::getModel('chordericons/chicons');
        $iconCollection = $iconModel->getCollection();
        foreach ($iconCollection as $icon) {
            $iconData = $icon->getData();
            $nameIcon = $iconData['name'];
            $nameIcon = trim($nameIcon);
            if (strtolower($nameIcon) == strtolower($name)) {
                return $iconData['icon_id'];
                break 1;
            }
        }
        return 'null';
    }
}
