<?php
/**
 * Collinsharper/Custom/Model/Cron.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chcustom
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @license   http://opensource.org/licenses/gpl-license.php  GNU General Public License (GPL)
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper Chcustom Cron Model
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chcustom
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @license   http://opensource.org/licenses/gpl-license.php  GNU General Public License (GPL)
 * @link      http://www.collinsharper.com/
 *
 */

class Collinsharper_Chcustom_Model_Cron
{
    const RETAIL_PACKAGING_SKU = 'retail_packaging';

    public function tmpTestInvoice()
    {
        $cr = Mage::getSingleton('core/resource');
        $read = $cr->getConnection('core_read');
        $sTable = $cr->getTablename('sales_flat_order');
        $iTable = $cr->getTablename('sales_flat_invoice');
        $o = array('entity_id' => 12);
        $sql = "select entity_id from {$iTable} where order_id = {$o['entity_id']}";
        $invoiceId = $read->fetchOne($sql);
        $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);

    }

    public function __updateRetailOrders()
    {
        mage::log(__METHOD__ . __LINE__ . " they have decided that they do not want boxes on the order? but I think thats incorrect. They still want to track the inventory of it?");
        return $this;
        $cr = Mage::getSingleton('core/resource');
        $read = $cr->getConnection('core_read');
        $sTable = $cr->getTablename('sales_flat_order');
        $iTable = $cr->getTablename('sales_flat_invoice');
        $siTable = $cr->getTablename('sales_flat_order_item');
        $srTable = $cr->getTablename('salesrule_coupon');
        $rTable = $cr->getTablename('salesrule');
        $retailSku = self::RETAIL_PACKAGING_SKU;
        $sql = "   select o.entity_id from {$sTable} o,
            {$srTable} c,
            {$rTable}  r
            where r.myretail = 1 and r.rule_id = c.rule_id and c.code = o.coupon_code and o.coupon_code is not null and to_days(o.created_at) >= to_days(now())-1 group by o.entity_id and o.entity_id not in (select order_id from {$siTable} where sku = '{$retailSku}' ) ";

        $orders = $read->fetchAll($sql);
        if(count($orders)) {

            $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $retailSku);

            foreach($orders as $o) {

                $order = Mage::getModel('sales/order')->load($o['entity_id']);

                $rowTotal = 0.00;
                $orderItem = Mage::getModel('sales/order_item')
                    ->setStoreId(NULL)
                    ->setQuoteItemId(NULL)
                    ->setQuoteParentItemId(NULL)
                    ->setProductId($product->getId())
                    ->setProductType($product->getTypeId())
                    ->setQtyBackordered(NULL)
                    ->setTotalQtyOrdered(1)
                    ->setQtyOrdered(1)
                    ->setName($product->getName())
                    ->setSku($product->getSku())
                    ->setPrice($product->getPrice())
                    ->setBasePrice($product->getPrice())
                    ->setOriginalPrice($product->getPrice())
                    ->setRowTotal($rowTotal)
                    ->setBaseRowTotal($rowTotal)
                    ->setOrder($order);

                $orderItem->save();

//                $sql = "select entity_id from {$iTable} where order_id = {$o['entity_id']}";
//                $invoiceId = $read->fetchOne($sql);
//                $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);


            }

        }
    }
}
