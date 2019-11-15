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
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 *
 *  PayPal-specific model for shopping cart items and totals
 * The main idea is to accommodate all possible totals into PayPal-compatible 4 totals and line items
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class TBT_Rewards_Model_Paypal_Cart extends Mage_Paypal_Model_Cart
{
    /**
     * Check the line items and totals according to PayPal business logic limitations
     * @see Mage_Paypal_Model_Cart::_validate()
     */
    protected function _validate()
    {
        if (Mage::helper('rewards/version')->isBaseMageVersionAtLeast('1.9.0.0')) {
            parent::_validate();
        } else {
            $this->_areItemsValid = true;
            $this->_areTotalsValid = false;

            $referenceAmount = $this->_salesEntity->getBaseGrandTotal();

            $itemsSubtotal = 0;
            foreach ($this->_items as $i) {
                $itemsSubtotal = $itemsSubtotal + $i['qty'] * $i['amount'];
            }
            $sum = $itemsSubtotal + $this->_totals[self::TOTAL_TAX];
            if (!$this->_isShippingAsItem) {
                $sum += $this->_totals[self::TOTAL_SHIPPING];
            }
            if (!$this->_isDiscountAsItem) {
                $sum -= $this->_totals[self::TOTAL_DISCOUNT];
            }
            /**
             * numbers are intentionally converted to strings because of possible comparison error
             * see http://php.net/float
             */
            // match sum of all the items and totals to the reference amount
            if (sprintf('%.4F', $sum) != sprintf('%.4F', $referenceAmount)) {
                $adjustment = $sum - $referenceAmount;
                $this->_totals[self::TOTAL_SUBTOTAL] = $this->_totals[self::TOTAL_SUBTOTAL] - $adjustment;
            }

            // PayPal requires to have discount less than items subtotal
            if (!$this->_isDiscountAsItem) {
                $this->_areTotalsValid = round($this->_totals[self::TOTAL_DISCOUNT], 4) < round($itemsSubtotal, 4);
            } else {
                $this->_areTotalsValid = $itemsSubtotal > 0.00001;
            }

            $this->_areItemsValid = $this->_areItemsValid && $this->_areTotalsValid;
        }
    }
}