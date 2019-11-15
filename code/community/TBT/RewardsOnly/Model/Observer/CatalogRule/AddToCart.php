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
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * This class is used as an observer class for add to cart events
 * @package     TBT_Rewards
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsOnly_Model_Observer_CatalogRule_AddToCart
    extends TBT_Rewards_Model_Observer_CatalogRule_AddToCart
{
    /**
     * Gets triggered on the 'checkout_cart_product_add_after' event to append points information
     * to a quote item upon being added to the cart
     *
     * @param Varien_Event_Observer $observer
     */
    public function appendPointsQuoteAfterAdd(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        
        if (!$event) {
            return $this;
        }

        $item    = $event->getQuoteItem();
        $product = $event->getProduct();
        $request = Mage::app()->getRequest();

        if (!$request || !$product || !$item) {
            return $this;
        }

        if ($item->getParentItem()) {
            $item = $item->getParentItem();
        }

        $applyRuleId = $request->getParam('redemption_rule');
        $applyRuleUses = $request->getParam('redemption_uses');
        $qty = $request->getParam('qty');
        
        /* BEGIN Rewards Only Mode Feature */

        $rp = Mage::getModel('rewardsonly/catalog_product')->wrap2($product);
        $ruleSelector = Mage::helper('rewardsonly/config')->getRedemptionSelectionAlgorithm();
        $ruleSelector->init($this->_getAggregatedCart()->getCustomer(), $rp);

        if ($ruleSelector->hasRule()) {
            if(Mage::helper('rewardsonly/config')->forceRedemptions()) {
                $applyRuleId = $ruleSelector->getRule()->getId();
                $applyRuleUses = (!empty($applyRuleUses)) ? $applyRuleUses : 1;
            }
        }

        /* END Rewards Only Mode Feature */

        try {
            Mage::getSingleton('rewards/catalogrule_service_processRules')
                ->writePointsToQuote(
                    $product, $applyRuleId, $applyRuleUses, $qty, $item
                );
        } catch (Exception $e) {
            Mage::helper('rewards')->notice($e->getMessage());
            Mage::getSingleton('core/session')->addError(
                Mage::helper('rewards')->__(
                    "An error occurred trying to apply the points redemption to the product in your cart: %s",
                    $e->getMessage()
                )
            );
        }
        
        return $this;
    }
}
