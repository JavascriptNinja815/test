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
 * Discount calculation model 
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Salesrule_Quote_Discount extends Mage_SalesRule_Model_Quote_Discount
{
    /**
     * Collect address discount amount
     *
     * @param   Mage_Sales_Model_Quote_Address $address
     * @return  Mage_SalesRule_Model_Quote_Discount
     * 
     * @see Mage_SalesRule_Model_Quote_Discount::collect()
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        if ($this->_calculator instanceof TBT_Rewards_Model_Salesrule_Discount_Validator) {
            $this->_calculator->resetDeltas();
        }

        $shouldApplyRulesToParent = Mage::getStoreConfig('rewards/general/apply_rules_to_parent');
        
        // Check settings whether to apply rules on parent items as well
        if (empty($shouldApplyRulesToParent)) {
            return parent::collect($address);
        } else {
            $grandparent = get_parent_class(get_parent_class($this));
            $grandparent::collect($address);

            $quote = $address->getQuote();
            $store = Mage::app()->getStore($quote->getStoreId());
            
            // This method does not exist in earlier magento versions
            if (method_exists($this->_calculator, 'reset')) {
                $this->_calculator->reset($address);
            }

            $items = $this->_getAddressItems($address);
            if (!count($items)) {
                return $this;
            }

            $eventArgs = array(
                'website_id'        => $store->getWebsiteId(),
                'customer_group_id' => $quote->getCustomerGroupId(),
                'coupon_code'       => $quote->getCouponCode(),
            );

            $this->_calculator->init($store->getWebsiteId(), $quote->getCustomerGroupId(), $quote->getCouponCode());
            $this->_calculator->initTotals($items, $address);

            $address->setDiscountDescription(array());
            
            // This method does not exist in earlier magento versions
            if (method_exists($this->_calculator, 'sortItemsByPriority')) {
                $items = $this->_calculator->sortItemsByPriority($items);
            }
            
            foreach ($items as $item) {
                if ($item->getNoDiscount()) {
                    $item->setDiscountAmount(0);
                    $item->setBaseDiscountAmount(0);
                }
                else {
                    /**
                     * Child item discount we calculate for parent
                     */
                    if ($item->getParentItemId()) {
                        continue;
                    }

                    $eventArgs['item'] = $item;
                    Mage::dispatchEvent('sales_quote_address_discount_item', $eventArgs);

                    $this->_calculator->process($item);
                    $this->_aggregateItemDiscount($item);
                    
                    if ($item->getHasChildren() && $item->isChildrenCalculated()) {
                        foreach ($item->getChildren() as $child) {
                            $this->_calculator->process($child);
                            $eventArgs['item'] = $child;
                            Mage::dispatchEvent('sales_quote_address_discount_item', $eventArgs);

                            $this->_aggregateItemDiscount($child);
                        }
                    }
                }
            }

            /**
             * process weee amount
             */
            if (Mage::helper('weee')->isEnabled() && Mage::helper('weee')->isDiscounted($store)) {
                $this->_calculator->processWeeeAmount($address, $items);
            }

            /**
             * Process shipping amount discount
             */
            $address->setShippingDiscountAmount(0);
            $address->setBaseShippingDiscountAmount(0);
            if ($address->getShippingAmount()) {
                $this->_calculator->processShippingAmount($address);
                $this->_addAmount(-$address->getShippingDiscountAmount());
                $this->_addBaseAmount(-$address->getBaseShippingDiscountAmount());
            }

            $this->_calculator->prepareDescription($address);
            return $this;
        }
    }
}
