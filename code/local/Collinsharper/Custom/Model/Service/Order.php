<?php
class Collinsharper_Custom_Model_Service_Order extends Mage_Sales_Model_Service_Order
{
    public function prepareInvoice($qtys = array())
    {
        $this->updateLocaleNumbers($qtys);
        $invoice = $this->_convertor->toInvoice($this->_order);
        $totalQty = 0;

        foreach ($this->_order->getAllItems() as $orderItem) {
            if (!$this->_canInvoiceItem($orderItem, array())) {
                continue;
            }

            if($orderItem->getProductId() == 25)
            {
                if(Mage::app()->getRequest()->getActionName() == 'save')
                {
                    //try{
                      $writeConnection = Mage::getSingleton("core/resource")->getConnection("core_write");
                      $readConnection  = Mage::getSingleton("core/resource")->getConnection("core_read");

                      //insert row sales_flat_shipment
                      $query = "insert into sales_flat_shipment "
                             . "( store_id, total_weight, total_qty, email_sent, order_id, customer_id, shipping_address_id, billing_address_id, shipment_status, created_at, updated_at, packages, shipping_label) values "
                             . "(:store_id,:total_weight,:total_qty,:email_sent,:order_id,:customer_id,:shipping_address_id,:billing_address_id,:shipment_status,:created_at,:updated_at,:packages,:shipping_label)";

                      $binds = array(
                          'store_id'    => $orderItem->getStoreId(),
                          'total_weight'    => $orderItem->getWeight(),
                          'total_qty'    => $orderItem->getQtyOrdered(),
                          'email_sent'    => '',
                          'order_id'    => $orderItem->getOrderId(),
                          'customer_id'    => '',
                          'shipping_address_id'    => '',
                          'billing_address_id'    => '',
                          'shipment_status'    => '',
                          'created_at'    => $orderItem->getCreatedAt(),
                          'updated_at'    => $orderItem->getUpdatedAt(),
                          'packages'    => '',
                          'shipping_label'    => ''
                      );
                      $writeConnection->query($query, $binds);

                      $parent_id = $writeConnection->lastInsertId();

                      //insert row sales_flat_shipment_item
                      $query = "insert into sales_flat_shipment_item "
                             . "( parent_id, row_total, price, weight, qty, product_id, order_item_id, additional_data, description, name, sku) values "
                             . "(:parent_id,:row_total,:price,:weight,:qty,:product_id,:order_item_id,:additional_data,:description,:name,:sku)";

                      $binds = array(
                          'parent_id'         => $parent_id,
                          'row_total'         => $orderItem->getRowTotal(),
                          'price'             => $orderItem->getPrice(),
                          'weight'            => $orderItem->getWeight(),
                          'qty'               => $orderItem->getQtyOrdered(),
                          'product_id'        => $orderItem->getProductId(),
                          'order_item_id'     => $orderItem->getItemId(),
                          'additional_data'   => $orderItem->getAdditionalData(),
                          'description'       => $orderItem->getDescription(),
                          'name'              => $orderItem->getName(),
                          'sku'               => $orderItem->getSku(),
                      );
                      $writeConnection->query($query, $binds);
                }
            }


            $item = $this->_convertor->itemToInvoiceItem($orderItem);
            if ($orderItem->isDummy()) {
                $qty = $orderItem->getQtyOrdered() ? $orderItem->getQtyOrdered() : 1;
            } else if (!empty($qtys)) {
                if (isset($qtys[$orderItem->getId()])) {
                    $qty = (float) $qtys[$orderItem->getId()];
                }
            } else {
                $qty = $orderItem->getQtyToInvoice();
            }
            $totalQty += $qty;
            $item->setQty($qty);
            $invoice->addItem($item);
        }
        $invoice->setTotalQty($totalQty);
        $invoice->collectTotals();
        $this->_order->getInvoiceCollection()->addItem($invoice);
        return $invoice;
    }
    /*
    protected function _canShipItem($item, $qtys=array())
    {
        if($item->getProductId() == 25) {
            return true;
        }

        if ($item->getIsVirtual() || $item->getLockedDoShip()) {
            return false;
        }
        $this->updateLocaleNumbers($qtys);

        if ($item->isDummy(true)) {
            if ($item->getHasChildren()) {
                if ($item->isShipSeparately()) {
                    return true;
                }
                foreach ($item->getChildrenItems() as $child) {
                    if ($child->getIsVirtual()) {
                        continue;
                    }
                    if (empty($qtys)) {
                        if ($child->getQtyToShip() > 0) {
                            return true;
                        }
                    } else {
                        if (isset($qtys[$child->getId()]) && $qtys[$child->getId()] > 0) {
                            return true;
                        }
                    }
                }
                return false;
            } else if($item->getParentItem()) {
                $parent = $item->getParentItem();
                if (empty($qtys)) {
                    return $parent->getQtyToShip() > 0;
                } else {
                    return isset($qtys[$parent->getId()]) && $qtys[$parent->getId()] > 0;
                }
            }
        } else {
            return $item->getQtyToShip()>0;
        }

    }
    public function prepareShipment($qtys = array())
    {
        $this->updateLocaleNumbers($qtys);
        $totalQty = 0;
        $shipment = $this->_convertor->toShipment($this->_order);
        foreach ($this->_order->getAllItems() as $orderItem) {
            if (!$this->_canShipItem($orderItem, $qtys)) {
                continue;
            }

            $item = $this->_convertor->itemToShipmentItem($orderItem);
            if($item->getProductId() == 25) {

                $qty = 1;
            }else {


              if ($orderItem->isDummy(true)) {
                  $qty = 0;
                  if (isset($qtys[$orderItem->getParentItemId()])) {
                      $productOptions = $orderItem->getProductOptions();
                      if (isset($productOptions['bundle_selection_attributes'])) {
                          $bundleSelectionAttributes = unserialize($productOptions['bundle_selection_attributes']);

                          if ($bundleSelectionAttributes) {
                              $qty = $bundleSelectionAttributes['qty'] * $qtys[$orderItem->getParentItemId()];
                              $qty = min($qty, $orderItem->getSimpleQtyToShip());

                              $item->setQty($qty);
                              $shipment->addItem($item);
                              continue;
                          } else {
                              $qty = 1;
                          }
                      }
                  } else {
                      $qty = 1;
                  }
              } else {
                  if (isset($qtys[$orderItem->getId()])) {
                      $qty = min($qtys[$orderItem->getId()], $orderItem->getQtyToShip());
                  } elseif (!count($qtys)) {
                      $qty = $orderItem->getQtyToShip();
                  } else {
                      continue;
                  }
              }
            }
            $totalQty += $qty;
            $item->setQty($qty);
            $shipment->addItem($item);
        }

        $shipment->setTotalQty($totalQty);
        return $shipment;
    }*/
}
