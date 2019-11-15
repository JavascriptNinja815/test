<?php
/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the SWEET TOOTH POINTS AND REWARDS 
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
 * Helper for the prices of products and quote items with monetary currencies
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Helper_Price extends Mage_Core_Helper_Abstract {
	
	const CURRENCY_RATE_ROUND = 4;
	
	/**
	 * This function reverses any prices that have already been converted to the current store
	 * currency rate.
	 * @param float $price
	 * @param float $target_currency_rate
	 * @param boolean $do_round : Should the price be rounded using the current store's rounding function?
	 */
	public function getReversedCurrencyPrice($price, $target_currency_rate = null, $do_round = true) {
		if ($target_currency_rate == null) {
			$cc = $this->_getAggregatedCart()->getStore()->getCurrentCurrency();
			$bc = 1 / ($this->_getAggregatedCart()->getStore ()->getBaseCurrency ()->getRate ( $cc ));
			$target_currency_rate = $bc;
		}
		$final_price = $price * $target_currency_rate;
		if ($do_round) {
			$final_price = $this->_getAggregatedCart()->getStore ()->roundPrice ( $final_price );
		}
		return $final_price;
	}
	
	/**
	 * Fetches the current store's currency or the one from the quote model.
	 * @param Mage_Sales_Model_Quote $quote [also accepts order model]
	 */
	public function getCurrencyRate($quote = null) {
		if ($quote->getStoreToQuoteRate () && $quote) {
			$c = round ( $quote->getStoreToQuoteRate (), 4 );
		} else {
			if ($quote) {
				$store = ($quote->getStore ()) ? $quote->getStore () : Mage::app ()->getStore ();
			} else {
				$store = $this->_getAggregatedCart()->getStore ();
			}
			
			$baseCurrency = $store->getBaseCurrency ();
			
			if ($quote) {
				$quoteCurrency = $quote->hasForcedCurrency () ? $quote->getForcedCurrency () : $store->getCurrentCurrency ();
			} else {
				$quoteCurrency = $store->getCurrentCurrency ();
			}
			
			$c = $baseCurrency->getRate ( $quoteCurrency );
		}
		return $c;
	
	}
	
	/**
	 * 
	 * this will take an item with row total $100 with 1% tax and change it to 
	 * $99 with $1 tax amount applied.
	 * @param unknown_type $item
	 */
	public function refactorTaxOnItem(&$item) {
		$store = $this->_getAggregatedCart()->getStore ();
		
		$tax = $item->getTaxPercent () / 100;
		$new_tax_amount = ($store->roundPrice ( $item->getRowTotal () * $tax ));
		$new_row_total = ( float ) ($store->roundPrice ( $item->getRowTotal () - $new_tax_amount ));
		
		if ($new_row_total <= - 0)
			$new_row_total = 0;
		$item->setRowTotal ( $new_row_total );
		
		if ($new_tax_amount <= - 0)
			$new_tax_amount = 0;
		$item->setTaxAmount ( $new_tax_amount );
		return $item;
	}
	
    /**
     *
     * @param decimal $baseAmount
     * @param Mage_Sales_Model_Quote $quote
     * @return decimal
     */
    public function getBaseCurrencyDelta($baseAmount, $quote)
    {
        $delta = 0;

        if (!$quote || !$quote->getId()) {
            return $delta;
        }

        $currencyRate = Mage::helper('rewards/price')->getCurrencyRate($quote);

        $baseToCurrencyAmount = $quote->getStore()->roundPrice($baseAmount * $currencyRate);
        $reversedBackAmount = Mage::helper('rewards/price')->getReversedCurrencyPrice($baseToCurrencyAmount, 1 / $currencyRate, false);

        $delta = $baseAmount - $reversedBackAmount;

        return $delta;
    }

    /**
     * Calculate Real Price for Product Type Composite
     *
     * @param Mage_Catalog_Model_Product $product
     * @param float $price
     * @param bool $isPercent
     * @param null|int $storeId
     * @return mixed
     */
    public function preparePriceProductTypeComposite($product, $price, $isPercent = false, $storeId = null)
    {
        if ($isPercent && !empty($price)) {
            $price = $product->getFinalPrice() * $price / 100;
        }

        if (empty($price)) {
            $price = 0;
        }

        $price = Mage::app()->getStore($storeId)->convertPrice($price);

        $price = Mage::app()->getStore($storeId)->roundPrice($price);

        $jsPrice = str_replace(',', '.', $price);

        return $jsPrice;
    }

    /**
     * Aggregation Cart instance
     * @return TBT_Rewards_Model_Sales_Aggregated_Cart
     */
    protected function _getAggregatedCart()
    {
        return Mage::getSingleton('rewards/sales_aggregated_cart');
    }

}
