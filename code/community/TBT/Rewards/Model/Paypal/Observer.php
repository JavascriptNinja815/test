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
 * This class is an observer that listens to the paypal_prepare_line_items event dispatched by the Mage_Paypal_Model_Cart
 * class.  As of Sweet Tooth 1.5.0.3, this replaces the rewrite of the Mage_Paypal_Model_Standard class with TBT_Rewards_Model_Paypal_Standard
 * The rewrite is still there for versions of Magento lower than 1.4.1.0 since those previous versions do not dispatch the paypal_prepare_line_items
 * event (and don't have the Mage_Paypal_Model_Cart class actually)
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Paypal_Observer extends Varien_Object
{

    /**
     * Prepare the catalog redemption rule discounts
     *
     * @deprecated We no longer need to subtract this from the grand total as this is automatically computed by
     * Magento, since we save each items row_total + tax_amount after ST Catalog discounts
     *
     * @param $o observer object
     *
     * @return    TBT_Rewards_Model_Paypal_Observer
     */
    public function prepare($o)
    {
        // Only run for Magento 1.4.2.x and higher
        if (!Mage::helper('rewards/version')->isBaseMageVersionAtLeast('1.4.2.0')) {
            return $this;
        }

        $event = $o->getEvent();
        if (!$event) {
            return $this;
        }

        $ppCart = $event->getPaypalCart();
        // IF the paypal cart object is not defined, we may be in a bad observer dispatch or Magento 1.4.1 (when the had crappy params for the observer)
        if (!$ppCart) {
            return $this;
        }

        $_quote = $ppCart->getSalesEntity();
        // we only want to continue if $_quote is an instace of Mage_Sales_Model_Quote, which will happen when using
        // Paypal Express, before being redirected to Paypal
        if ($_quote instanceof Mage_Sales_Model_Order) {
            return $this;
        }

        $pps = Mage::getModel('rewards/paypal_standard');
        $discountAmount = $pps->getDiscountDisplacement($_quote);

        if ($discountAmount <= 0) {
            return $this; // no discount needed
        }

        // we need to subtract the ST catalog discount amount from the quote subtotal
        $ppCart->updateTotal(Mage_Paypal_Model_Cart::TOTAL_SUBTOTAL, -$discountAmount);

        return $this;
    }
}
