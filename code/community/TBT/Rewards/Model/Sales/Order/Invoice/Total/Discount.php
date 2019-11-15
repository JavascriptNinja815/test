<?php
/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).

 * The Open Software License is available at this URL: 
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * DISCLAIMER
 * 
 * By adding to, editing, or in any way modifying this code, Sweet Tooth is 
 * not held liable for any inconsistencies or abnormalities in the 
 * behaviour of this code. 
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Sweet Tooth, outlined in the 
 * provided Sweet Tooth License. 
 * Upon discovery of modified code in the process of support, the Licensee 
 * is still held accountable for any and all billable time Sweet Tooth spent 
 * during the support process.
 * Sweet Tooth does not guarantee compatibility with any other framework extension. 
 * Sweet Tooth is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to 
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy 
 * immediately.
 * 
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales Invoice Discount Total 
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Sales_Order_Invoice_Total_Discount extends Mage_Sales_Model_Order_Invoice_Total_Discount
{
    /**
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return \TBT_Rewrads_Model_Sales_Order_Invoice_Total_Discount
     * 
     * @see Mage_Sales_Model_Order_Invoice_Total_Discount::collect()
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $shouldApplyRulesToParent = Mage::getStoreConfig('rewards/general/apply_rules_to_parent');
        
        // Check settings whether to apply rules on parent items as well
        if (!$shouldApplyRulesToParent) {
            $return = parent::collect($invoice);

            $this->_prepareInvoiceRewardsCartDiscountMap($invoice);

            return $return;
        } else {
            $invoice->setDiscountAmount(0);
            $invoice->setBaseDiscountAmount(0);

            $totalDiscountAmount     = 0;
            $baseTotalDiscountAmount = 0;

            /**
             * Checking if shipping discount was added in previous invoices.
             * So basically if we have invoice with positive discount and it
             * was not canceled we don't add shipping discount to this one.
             */
            $addShippingDicount = true;
            foreach ($invoice->getOrder()->getInvoiceCollection() as $previusInvoice) {
                if ($previusInvoice->getDiscountAmount()) {
                    $addShippingDicount = false;
                }
            }

            if ($addShippingDicount) {
                $totalDiscountAmount     = $totalDiscountAmount + $invoice->getOrder()->getShippingDiscountAmount();
                $baseTotalDiscountAmount = $baseTotalDiscountAmount + $invoice->getOrder()->getBaseShippingDiscountAmount();
            }

            /** @var $item Mage_Sales_Model_Order_Invoice_Item */
            foreach ($invoice->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem->getParentItem() && !$orderItem->isChildrenCalculated()) {
                     continue;
                }

                $orderItemDiscount      = (float) $orderItem->getDiscountAmount();
                $baseOrderItemDiscount  = (float) $orderItem->getBaseDiscountAmount();
                $orderItemQty       = $orderItem->getQtyOrdered();

                if ($orderItemDiscount && $orderItemQty) {

                    /**
                     * Resolve rounding problems
                     *
                     * We dont want to include the weee discount amount as the right amount
                     * is added when calculating the taxes.
                     *
                     * Also the subtotal is without weee
                     */

                    $discount = $orderItemDiscount - $orderItem->getDiscountInvoiced();
                    $baseDiscount = $baseOrderItemDiscount - $orderItem->getBaseDiscountInvoiced();

                    if (!$item->isLast()) {
                        $activeQty = $orderItemQty - $orderItem->getQtyInvoiced();
                        if (method_exists('roundPrice', $invoice)) {
                            $discount = $invoice->roundPrice($discount / $activeQty * $item->getQty(), 'regular', true);
                            $baseDiscount = $invoice->roundPrice($baseDiscount / $activeQty * $item->getQty(), 'base', true);
                        } else {
                            $discount = $invoice->getStore()->roundPrice($discount / $activeQty * $item->getQty(), 'regular', true);
                            $baseDiscount = $invoice->getStore()->roundPrice($baseDiscount / $activeQty * $item->getQty(), 'base', true);
                        }
                    }

                    $item->setDiscountAmount($discount);
                    $item->setBaseDiscountAmount($baseDiscount);

                    $totalDiscountAmount += $discount;
                    $baseTotalDiscountAmount += $baseDiscount;
                }
            }

            $invoice->setDiscountAmount(-$totalDiscountAmount);
            $invoice->setBaseDiscountAmount(-$baseTotalDiscountAmount);

            $invoice->setGrandTotal($invoice->getGrandTotal() - $totalDiscountAmount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() - $baseTotalDiscountAmount);

            $this->_prepareInvoiceRewardsCartDiscountMap($invoice);
            
            return $this;
        }
    }

    /**
     * Prepare Invoice Rewards Cart Discount Map including partial invoices
     * @param Mage_Sales_Model_Order_Creditmemo $invoice
     * @return \TBT_Rewards_Model_Sales_Order_Creditmemo_Total_Discount
     */
    protected function _prepareInvoiceRewardsCartDiscountMap(Mage_Sales_Model_Order_Invoice &$invoice)
    {
        $order = $invoice->getOrder();

        if (!$order || !$order->getId()) {
            return $this;
        }

        $delta = 0;
        $deltaBase = 0;
        
        foreach ($invoice->getAllItems() as $invoiceItem) {
            $orderItem = $invoiceItem->getOrderItem();

            $qtyOrdered = $orderItem->getQtyOrdered();
            $qtyToInvoice = $invoiceItem->getQty();

            $orderItemRewardsCartMap = $order->getItemRewardsCartDiscountMapItems($orderItem);

            $rate = (float) ($qtyToInvoice / $qtyOrdered);

            if (count($orderItemRewardsCartMap) < 1) {
                continue;
            }

            $newInvoiceItemMap = array();

            foreach ($orderItemRewardsCartMap as $ruleId => $mapItem) {
                $newInvoiceItemMap[$ruleId] = $mapItem;

                $newDiscountAmount = $mapItem['discount_amount'] * $rate + 0.00001 + $delta;
                $newBaseDiscountAmount = $mapItem['base_discount_amount'] * $rate + 0.00001 + $deltaBase;

                $newInvoiceItemMap[$ruleId]['discount_amount'] = max(0,$order->getStore()->roundPrice($newDiscountAmount));
                $newInvoiceItemMap[$ruleId]['base_discount_amount'] = max(0,$order->getStore()->roundPrice($newBaseDiscountAmount));

                $delta = $newDiscountAmount - $newInvoiceItemMap[$ruleId]['discount_amount'];
                $deltaBase = $newBaseDiscountAmount - $newInvoiceItemMap[$ruleId]['base_discount_amount'];
            }

            $this->_appendItemMapToInvoice($invoice, $newInvoiceItemMap);
            
            $jsonItemMapEncoded = json_encode($newInvoiceItemMap);
            $invoiceItem->setRewardsCartDiscountMap($jsonItemMapEncoded);
        }

        return $this;
    }

    /**
     * Merge Invoice Item Rewards Cart Discount Map into invoice
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param array $invoiceItemMap
     * @return \TBT_Rewards_Model_Sales_Order_Creditmemo_Total_Discount
     */
    protected function _appendItemMapToInvoice(&$invoice, $invoiceItemMap)
    {
        /**
         * Append Rewards Cart Discount Amounts to Invoice
         */
        $rewardsDiscountMap = $invoice->getRewardsCartDiscountMap();

        if (is_null($rewardsDiscountMap)) {
            $rewardsDiscountMap = array();
        } else {
            $rewardsDiscountMap = json_decode($invoice->getRewardsCartDiscountMap(), true);
        }

        if (!is_array($rewardsDiscountMap)) {
            return $this;
        }

        foreach ($invoiceItemMap as $ruleId => $invoiceItemMapEntry) {
            if (!isset($rewardsDiscountMap[$ruleId])) {
                $rewardsDiscountMap[$ruleId] = $invoiceItemMapEntry;
            } else {
                $rewardsDiscountMap[$ruleId]['base_discount_amount'] += $invoiceItemMapEntry['base_discount_amount'];
                $rewardsDiscountMap[$ruleId]['discount_amount'] += $invoiceItemMapEntry['discount_amount'];
            }
        }

        $jsonRewardsCartDiscountMap = json_encode($rewardsDiscountMap);
        $invoice->setRewardsCartDiscountMap($jsonRewardsCartDiscountMap);

        return $this;
    }
}
