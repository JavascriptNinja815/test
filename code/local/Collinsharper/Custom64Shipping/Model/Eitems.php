<?php
/**
 * Open Commerce LLC Commercial Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Commerce LLC Commercial Extension License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://store.opencommercellc.com/license/commercial-license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@opencommercellc.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this package to newer
 * versions in the future.
 *
 * @category   OpenCommerce
 * @package    OpenCommerce_OrderEdit
 * @copyright  Copyright (c) 2013 Open Commerce LLC
 * @license    http://store.opencommercellc.com/license/commercial-license
 */

class Collinsharper_Custom64Shipping_Model_Eitems extends TinyBrick_OrderEdit_Model_Edit_Updater_Type_Eitems
{
    /**
     * Edits existing items of the order
     * @param TinyBrick_OrderEdit_Model_Order $order
     * @param array $data
     * @return boolean
     */
    public function edit(TinyBrick_OrderEdit_Model_Order $order, $data = array())
    {
        $comment = "";

        // check for both qty increase and qty decrease; we don't allow this
        $this->_detectQtyIncreaseAndDecrease($order, $data);

        foreach($data['id'] as $key => $itemId) {
            $item = $order->getItemById($itemId);



            $product = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToFilter('sku', $item->getSku())
                ->addAttributeToSelect('*')
                ->getFirstItem();

            if ($data['remove'][$key]) {

                // remove item
                $comment .= "Removed Item(SKU): " . $item->getSku() . "<br />";

                $oldQty = $item->getQtyOrdered();
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                $stockItem->addQty($oldQty);
                $stockItem->save();

                $order->removeItem($itemId);
            } else {
                // edit item

                // set this here so we can change it.
                $oldQty = $item->getQtyOrdered();

                // save old values
                $oldArray = array(
                    'sku'       => $item->getSku(),
                    'price'     => $item->getPrice(),
                    'discount'  => $item->getDiscountAmount(),
                    'qty'       => $item->getQtyOrdered()
                );

                // this will always be reel color for now..
                if (!empty($data['option']) && is_array($data['option'])) {
                    foreach ($data['option']  as $optionKey => $option) {
                        if ($optionKey < ($key + 1) * 2 && $optionKey >= $key * 2 && $option != '')
                            $this->_handleOptionChange($option, $item);
                    }
                } else if (isset($data['option'][$key])) {
                    $option = $data['option'][$key];
                    $this->_handleOptionChange($option, $item);
                }

                // set new values
                $item->setPrice($data['price'][$key]);
                $item->setBasePrice($data['price'][$key]);
                $item->setBaseOriginalPrice($data['price'][$key]);
                $item->setOriginalPrice($data['price'][$key]);
                $item->setBaseRowTotal($data['price'][$key]);
                if ($data['discount'][$key]) {
                    $item->setDiscountAmount($data['discount'][$key]);
                    $item->setBaseDiscountAmount($data['discount'][$key]);
                }

                if ($data['qty'][$key]) {

                    $item->setQtyOrdered($data['qty'][$key]);
                    $newQty = $item->getQtyOrdered();

                    if ($newQty > $oldQty) {
                        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                        $stockItem->subtractQty($newQty-$oldQty);
                        $stockItem->save();
                    }
                    else if ($newQty < $oldQty) {
                        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                        $stockItem->addQty($oldQty-$newQty);
                        $stockItem->save();
                        // update the qty array on the order for creditmemo use during reconciliation
                        $this->_updateCreditmemoData($order, $item, $oldQty, $newQty);
                    }
                }
                $item->save();

                $newArray = array(
                    'sku'       => $item->getSku(),
                    'price'     => $item->getPrice(),
                    'discount'  => $item->getDiscountAmount(),
                    'qty'       => $item->getQtyOrdered()
                );

                $comment .= $this->_buildOrderEditItemComment($oldArray, $newArray, $item->getSku(), $order->getBaseCurrency());
            }
        }

        if($comment != "") {
            $comment = "Edited items:<br />" . $comment . "<br />";
            return $comment;
        }
        return true;
    }

    /**
     * Creates/updates the data array on the order holding the quantities of each item to refund
     * for use with a future creditmemo, i.e. if the qty of item with ID 123 is decreased by 2,
     * then $order->getOrdereditCreditmemoData() should give:
     * array(
     *     'qtys': array(
     *         '123' => 2
     *     )
     * )
     *
     * @param $order, $item, $oldQty, $newQty
     * @return $this
     */
    protected function _updateCreditmemoData($order, $item, $oldQty, $newQty)
    {
//var_dump($item->getQtyToRefund()); die();
        $qtyDelta = $oldQty - $newQty;

        if ($qtyDelta <= 0) {
            throw new Exception(Mage::helper('orderedit')->__('Cannot refund 0 or less qty of item with SKU %s (given %s).',
                $item->getSku(),
                $qtyDelta
            ));
        }

        /*    if ($qtyDelta > $item->getQtyToRefund()) {
                throw new Exception(Mage::helper('orderedit')->__('Cannot refund more than %s qty of item with SKU %s.',
                    $item->getQtyToRefund(),
                    $item->getSku()
                ));
            }*/

        $creditmemoData = $order->getOrdereditCreditmemoData();
        // no isset checks here because the array structure should have been initialized on the order earlier during this request.
        // if it's not, there is a serious problem and continuing might cause the entire order to be refunded, rather than parts.
        $creditmemoData['qtys'][$item->getItemId()] += $qtyDelta;
        $order->setOrdereditCreditmemoData($creditmemoData);

        return $this;
    }

    /**
     * *Collinsharper added*
     * Throws an exception if $data contains both an increase and a decrease in qty.
     *
     * @throws Exception
     * @param $order, $data
     */
    protected function _detectQtyIncreaseAndDecrease($order, $data)
    {
        $qtyIncrease = false;
        $qtyDecrease = false;

        if (!isset($data['id'])) {
            return $this;
        }

        foreach ($data['id'] as $key => $itemId) {
            if (!isset($data['qty']) || !isset($data['qty'][$key])) {
                continue;
            }

            $item = $order->getItemById($itemId);
            $oldQty = $item->getQtyOrdered();
            $newQty = $data['qty'][$key];

            if ($oldQty < $newQty) {
                $qtyIncrease = true;
            } else if ($oldQty > $newQty) {
                $qtyDecrease = true;
            }
            if ($qtyIncrease && $qtyDecrease) {
                throw new Exception('Both an increase and a decrease in qty detected. To do this, please cancel this order and create a new one.');
            }
        }

        return $this;
    }


    /**
     * @return bool true if the option was changed, else false
     */
    protected function _handleOptionChange($newOption, $item)
    {
        list($optionId, $optionValue, $partialSku, $optionLabel) = explode("_", $newOption, 4);
        // TODO: stub out code to update price (*not necesary at this time)

        $productOptions = unserialize($item->getData('product_options'));
        $buyRequest = $productOptions['info_buyRequest'];

        $oldOption =  isset($buyRequest['options'][$optionId]) ? $buyRequest['options'][$optionId] : false;

        if (!$oldOption || ($oldOption == $optionValue)) {
            return false;
        }

        // put the item back in stock.
        // decrement the new one.
        $doldQty = $item->getQtyOrdered();
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($item->getProduct());
        $stockItem->addQty($doldQty);
        $stockItem->save();


        // change the buy Req
        $buyRequest['options'][$optionId] = $optionValue;
        foreach($productOptions['options'] as $k => $opt) {
            if($opt['option_id'] == $optionId) {
                $productOptions['options'][$k]['value'] = $optionLabel;
                $productOptions['options'][$k]['print_value'] = $optionLabel;
                $productOptions['options'][$k]['option_value'] = $optionValue;
            }
        }

        $skuParts = explode('-', $item->getSku(), 2);
        $newSku = $skuParts[0] . '-' . $partialSku;

        $productOptions['info_buyRequest'] = $buyRequest;
        $item->setData('product_options', serialize($productOptions));

        $item->setSku($newSku);

        return true;
    }

    protected function _buildOrderEditItemComment($oldArray, $newArray, $sku, $currency)
    {
        $comment = '';

        if ($newArray['sku'] != $oldArray['sku']) {
            $comment = "Changed SKU from {$oldArray['sku']} to {$newArray['sku']}<br />";
        }

        if ($newArray['price'] != $oldArray['price'] || $newArray['discount'] != $oldArray['discount'] || $newArray['qty'] != $oldArray['qty']) {
            $comment = "Edited item " . $sku . "<br />";
            if ($newArray['price'] != $oldArray['price']) {
                //  $comment .= "Price FROM: " . $currency->format($oldArray['price'], array(), false) . " TO: " . $currency->formatPrecision($newArray['price'], array(), false) . "<br />";
                $comment .= "Price FROM: " .  $oldArray['price']  . " TO: " .  $newArray['price']  . "<br />";
            }
            if ($newArray['discount'] != $oldArray['discount']) {
                $comment .= "Discount FROM: " . $oldArray['discount'] . " TO: " . $newArray['discount'] . "<br />";
            }
            if ($newArray['qty'] != $oldArray['qty']) {
                $comment .= "Qty FROM: " . $oldArray['qty'] . " TO: " . $newArray['qty'] . "<br />";
            }
        }

        return $comment;
    }

}
