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
 * Sales Credit Memo Discount Total 
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Sales_Order_Creditmemo_Total_Discount extends Mage_Sales_Model_Order_Creditmemo_Total_Discount
{
    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @return \TBT_Rewrads_Model_Sales_Order_Creditmemo_Total_Discount
     * 
     * @see Mage_Sales_Model_Order_Creditmemo_Total_Discount::collect()
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $shouldApplyRulesToParent = Mage::getStoreConfig('rewards/general/apply_rules_to_parent');
        
        // Check settings whether to apply rules on parent items as well
        if (!$shouldApplyRulesToParent) {
            $return = parent::collect($creditmemo);

            $this->_prepareCreditmemoRewardsCartDiscountMap($creditmemo);

            return $return;
        } else {
            $creditmemo->setDiscountAmount(0);
            $creditmemo->setBaseDiscountAmount(0);

            $order = $creditmemo->getOrder();

            $totalDiscountAmount = 0;
            $baseTotalDiscountAmount = 0;

            /**
             * Calculate how much shipping discount should be applied
             * basing on how much shipping should be refunded.
             */
            $baseShippingAmount = $creditmemo->getBaseShippingAmount();
            if ($baseShippingAmount) {
                $baseShippingDiscount = $baseShippingAmount * $order->getBaseShippingDiscountAmount() / $order->getBaseShippingAmount();
                $shippingDiscount = $order->getShippingAmount() * $baseShippingDiscount / $order->getBaseShippingAmount();
                $totalDiscountAmount = $totalDiscountAmount + $shippingDiscount;
                $baseTotalDiscountAmount = $baseTotalDiscountAmount + $baseShippingDiscount;
            }

            /** @var $item Mage_Sales_Model_Order_Invoice_Item */
            foreach ($creditmemo->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();

                if ($orderItem->getParentItem() && !$orderItem->isChildrenCalculated()) {
                    continue;
                }

                $orderItemDiscount      = (float) $orderItem->getDiscountInvoiced();
                $baseOrderItemDiscount  = (float) $orderItem->getBaseDiscountInvoiced();
                $orderItemQty           = $orderItem->getQtyInvoiced();

                if ($orderItemDiscount && $orderItemQty) {
                    $discount = $orderItemDiscount - $orderItem->getDiscountRefunded();
                    $baseDiscount = $baseOrderItemDiscount - $orderItem->getBaseDiscountRefunded();
                    if (!$item->isLast()) {
                        $availableQty = $orderItemQty - $orderItem->getQtyRefunded();
                        if (method_exists('roundPrice', $creditmemo)) {
                            $discount = $creditmemo->roundPrice(
                                $discount / $availableQty * $item->getQty(), 'regular', true
                            );
                            $baseDiscount = $creditmemo->roundPrice(
                                $baseDiscount / $availableQty * $item->getQty(), 'base', true
                            );
                        } else {
                            $discount = $creditmemo->getStore()->roundPrice(
                                $discount / $availableQty * $item->getQty(), 'regular', true
                            );
                            $baseDiscount = $creditmemo->getStore()->roundPrice(
                                $baseDiscount / $availableQty * $item->getQty(), 'base', true
                            );
                        }
                    }

                    $totalDiscountAmount += $discount;
                    $baseTotalDiscountAmount += $baseDiscount;

                    $item->setDiscountAmount($discount);
                    $item->setBaseDiscountAmount($baseDiscount);
                }
            }

            $creditmemo->setDiscountAmount(-$totalDiscountAmount);
            $creditmemo->setBaseDiscountAmount(-$baseTotalDiscountAmount);

            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() - $totalDiscountAmount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() - $baseTotalDiscountAmount);

            $this->_prepareCreditmemoRewardsCartDiscountMap($creditmemo);

            return $this;
        }
    }

    /**
     * Prepare Creditmemo Rewards Cart Discount Map including partial creditmemos
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @return \TBT_Rewards_Model_Sales_Order_Creditmemo_Total_Discount
     */
    protected function _prepareCreditmemoRewardsCartDiscountMap(Mage_Sales_Model_Order_Creditmemo &$creditmemo)
    {
        $order = $creditmemo->getOrder();

        if (!$order || !$order->getId()) {
            return $this;
        }

        $delta = 0;
        $deltaBase = 0;

        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            $orderItem = $creditmemoItem->getOrderItem();

            $qtyOrdered = $orderItem->getQtyOrdered();
            $qtyToCreditmemo = $creditmemoItem->getQty();

            $orderItemRewardsCartMap = $order->getItemRewardsCartDiscountMapItems($orderItem);

            $rate = (float) ($qtyToCreditmemo / $qtyOrdered);

            if (count($orderItemRewardsCartMap) < 1) {
                continue;
            }

            $newCreditmemoItemMap = array();

            foreach ($orderItemRewardsCartMap as $ruleId => $mapItem) {
                $newCreditmemoItemMap[$ruleId] = $mapItem;

                $newDiscountAmount = $mapItem['discount_amount'] * $rate + 0.00001 + $delta;
                $newBaseDiscountAmount = $mapItem['base_discount_amount'] * $rate + 0.00001 + $deltaBase;

                $newCreditmemoItemMap[$ruleId]['discount_amount'] = max(0,$order->getStore()->roundPrice($newDiscountAmount));
                $newCreditmemoItemMap[$ruleId]['base_discount_amount'] = max(0,$order->getStore()->roundPrice($newBaseDiscountAmount));

                $delta = $newDiscountAmount - $newCreditmemoItemMap[$ruleId]['discount_amount'];
                $deltaBase = $newBaseDiscountAmount - $newCreditmemoItemMap[$ruleId]['base_discount_amount'];
            }

            $this->_appendItemMapToCreditmemo($creditmemo, $newCreditmemoItemMap);

            $jsonItemMapEncoded = json_encode($newCreditmemoItemMap);
            $creditmemoItem->setRewardsCartDiscountMap($jsonItemMapEncoded);
        }

        return $this;
    }

    /**
     * Merge Creditmemo Item Rewards Cart Discount Map into creditmemo
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param array $creditmemoItemMap
     * @return \TBT_Rewards_Model_Sales_Order_Creditmemo_Total_Discount
     */
    protected function _appendItemMapToCreditmemo(&$creditmemo, $creditmemoItemMap)
    {
        /**
         * Append Rewards Cart Discount Amounts to Invoice
         */
        $rewardsDiscountMap = $creditmemo->getRewardsCartDiscountMap();

        if (is_null($rewardsDiscountMap)) {
            $rewardsDiscountMap = array();
        } else {
            $rewardsDiscountMap = json_decode($creditmemo->getRewardsCartDiscountMap(), true);
        }

        if (!is_array($rewardsDiscountMap)) {
            return $this;
        }

        foreach ($creditmemoItemMap as $ruleId => $creditmemoItemMapEntry) {
            if (!isset($rewardsDiscountMap[$ruleId])) {
                $rewardsDiscountMap[$ruleId] = $creditmemoItemMapEntry;
            } else {
                $rewardsDiscountMap[$ruleId]['base_discount_amount'] += $creditmemoItemMapEntry['base_discount_amount'];
                $rewardsDiscountMap[$ruleId]['discount_amount'] += $creditmemoItemMapEntry['discount_amount'];
            }
        }

        $jsonRewardsCartDiscountMap = json_encode($rewardsDiscountMap);
        $creditmemo->setRewardsCartDiscountMap($jsonRewardsCartDiscountMap);

        return $this;
    }
}
