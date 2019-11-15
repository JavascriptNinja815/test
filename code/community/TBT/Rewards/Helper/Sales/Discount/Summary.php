<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 * https://www.sweettoothrewards.com/terms-of-service
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
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper class used in rewards discount full summary display
 * @package     TBT_Rewards
 * @subpackage  Block
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Helper_Sales_Discount_Summary
    extends Mage_Core_Helper_Abstract
{
    /**
     * Get Other Discount Amount value (magento default discount amount)
     * @param mixed $source
     * @param boolean $returnBoth
     * @return int|array
     */
    public function getOtherDiscountAmount($source = null, $returnBoth = false)
    {
        $totalDiscountAmount = 0;
        $baseTotalDiscountAmount = 0;

        if ($source instanceof Mage_Sales_Model_Order) {
            $totalDiscountAmount = $source->getDiscountAmount();
            $baseTotalDiscountAmount = $source->getBaseDiscountAmount();
        } elseif ($source instanceof Mage_Sales_Model_Order_Invoice) {
            $totalDiscountAmount = $source->getDiscountAmount();
            $baseTotalDiscountAmount = $source->getBaseDiscountAmount();
        } elseif ($source instanceof Mage_Sales_Model_Order_Creditmemo) {
            $totalDiscountAmount = $source->getDiscountAmount();
            $baseTotalDiscountAmount = $source->getBaseDiscountAmount();
        } else {
            $source = Mage::getSingleton('rewards/sales_aggregated_cart')->getQuote();

            if (!$source) {
                return 0;
            }

            $totalDiscountAmount = 0;
            $baseTotalDiscountAmount = 0;
            foreach ($source->getAllItems() as $quoteItem) {
                $totalDiscountAmount += $quoteItem->getDiscountAmount();
                $baseTotalDiscountAmount += $quoteItem->getBaseDiscountAmount();
            }
        }

        if (!$source) {
            return 0;
        }

        $rewardsSum = 0;
        $baseRewardsSum = 0;

        $rewardsDiscountMap = $this->getRewardsDiscountMap($source);

        foreach ($rewardsDiscountMap as $rewardsMapEntry) {
            $rewardsSum += $rewardsMapEntry['discount_amount'];
            $baseRewardsSum += $rewardsMapEntry['base_discount_amount'];
        }

        if ($returnBoth) {
            return array(
                (abs($totalDiscountAmount) - $rewardsSum),
                (abs($baseTotalDiscountAmount) - $baseRewardsSum)
            );
        }

        return (abs($totalDiscountAmount) - $rewardsSum);
    }

    /**
     * Rewards Discount Map Array
     * @param mixed $source
     * @return array
     */
    public function getRewardsDiscountMap($source = null)
    {
        $sourceForDiscountMap = $source;

        if ($source instanceof Mage_Sales_Model_Order) {
            $discountsList = $source->getRewardsCartDiscountMapItems();
        } elseif ($source instanceof Mage_Sales_Model_Order_Invoice) {
            $discountsList = $source->getOrder()->getInvoiceRewardsCartDiscountMapItems($source);
        } elseif ($source instanceof Mage_Sales_Model_Order_Creditmemo) {
            $discountsList = $source->getOrder()->getCreditmemoRewardsCartDiscountMapItems($source);
        } else {
            $sourceForDiscountMap = Mage::getSingleton('rewards/sales_aggregated_cart')->getQuote();
            $discountsList = $sourceForDiscountMap->getRewardsCartDiscountMapItems();
        }

        return $discountsList;
    }

    /**
     * Checks if rewards discount map has entries
     * @param mixed $source
     * @return boolean
     */
    public function hasRewardsDiscountMap($source = null)
    {
        $rewards = $this->getRewardsDiscountMap($source);

        $numberOfRewards = count($rewards);

        return ($numberOfRewards > 0) ? true : false;
    }

    public function getTotalLabel($total, $source = null)
    {
        $totalLabel = "";
        if ($source instanceof Mage_Sales_Model_Order) {
            $totalLabel = $total->getLabel();
        } elseif ($source instanceof Mage_Sales_Model_Order_Invoice) {
            $totalLabel = $total->getLabel();
        } elseif ($source instanceof Mage_Sales_Model_Order_Creditmemo) {
            $totalLabel = $total->getLabel();
        } else {
            $totalLabel = $total->getTitle();
        }

        return $totalLabel;
    }

    /**
     * Format price to Order Currency Code
     * @param float $amount
     * @param mixed $source
     * @return string
     */
    public function getOrderCurrencyPrice($amount, $source = null)
    {
        if (
            ! $source instanceof Mage_Sales_Model_Order
            && ! $source instanceof Mage_Sales_Model_Order_Invoice
            && ! $source instanceof Mage_Sales_Model_Order_Creditmemo
        ) {
            return Mage::helper('core')->formatPrice($amount);
        }

        $priceFormated = Mage::app()->getLocale()
            ->currency($source->getOrderCurrencyCode())->toCurrency($amount);

        return $priceFormated;
    }

    /**
     * Format price to Quote Currency Code
     * @param float $amount
     * @param mixed $source
     * @return string
     */
    public function getQuoteCurrencyPrice($amount, $source = null)
    {
        if (!$source) {
            $source = Mage::getSingleton('rewards/sales_aggregated_cart')->getQuote();
        }
        
        if (! $source instanceof Mage_Sales_Model_Quote
            && ! Mage::app()->getStore()->isAdmin()
        ) {
            return Mage::helper('core')->formatPrice($amount);
        }

        $priceFormated = Mage::app()->getLocale()
            ->currency($source->getQuoteCurrencyCode())->toCurrency($amount);

        return $priceFormated;
    }
}
