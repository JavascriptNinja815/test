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
 * @package    [TBT_RewardsPointsOnly]
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Main RewardsPointsOnly Helper
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Is Points Only feature enabled
     * @return boolean
     */
    public function isPointsOnlyEnabled()
    {
        if (!Mage::helper('rewards/version')->isBaseMageVersionAtLeast("1.7.0")) {
            return false;
        }
        
        return (bool) Mage::getStoreConfig('rewards/general/pointsonly_enabled');
    }

    /**
     * Check if the price can be shown as points
     * @return boolean
     */
    public function showPointsAsPrice()
    {
    	return true;
    }

    /**
     * Validates that a catalog rule is properly configured based on other admin settings.
     * This can be used to auto-update Magento config settings in the event that yoru settings are not ready.
     **/
    public function validatePointsOnlyRuleAdminSettings($rule, $displayWarning = true)
    {
        if ($rule instanceof TBT_RewardsPointsOnly_Model_Rule) {

            // If it's a give by profit rule make sure the config is setting the cost attribute as used_in_product_listing=true
            if ($rule->getPointsAction() == 'give_by_profit') {
                $attributeCode = 'cost';
                $_entityTypeId = Mage::getModel('eav/entity')
                    ->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();

                $attrModel = Mage::getModel('catalog/resource_eav_attribute')
                    ->loadByCode($_entityTypeId, $attributeCode);

                if (!$attrModel->getUsedInProductListing()) {
                    $attrModel->setUsedInProductListing(true)->save();

                    $manageAttrUrl = Mage::helper('adminhtml')
                        ->getUrl(
                            'adminhtml/catalog_product_attribute/edit',
                            array('attribute_id' => $attrModel->getId())
                        );

                    $warningMsg = Mage::helper('rewards')
                        ->__("We enabled the 'Used in Product Listing' option for the 'cost' attribute configuration of your store in order to optimize speed for your rewards rules that rely on product profits. <br />To disable this again, you can manage your product 'cost' attribute in [link]<i>Catalog > Attributes > Manage atrributes</i>[/link] section.");
                    $warningMsg = str_replace('[link]', "<a href='". $manageAttrUrl ."'>", $warningMsg);
                    $warningMsg = str_replace('[/link]', "</a>", $warningMsg);

                    if ($displayWarning) {
                        Mage::getSingleton ( 'adminhtml/session' )->addNotice($warningMsg);
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Helper method to check if product or item is points only valid
     * @param Mage_Catalog_Model_Product|Mage_Sales_Model_Quote_Item $itemOrProduct
     * @return boolean
     */
    public function isPointsOnly($itemOrProduct)
    {
        if (
            $itemOrProduct instanceof Mage_Sales_Model_Quote_Item
            || $itemOrProduct instanceof Mage_Sales_Model_Order_Item
        ) {
            if ($itemOrProduct->getPrice() !== 0) {
                return false;
            }

            $ruleData = Mage::helper('rewards')->unhashIt($itemOrProduct->getRewardsPointsonlyHash());

            if (count($ruleData) < 1) {
                return false;
            }

            return true;
        } elseif ($itemOrProduct instanceof Mage_Catalog_Model_Product) {
            /**
             * change with indexer
             */
            $aggregatedCart = Mage::getSingleton('rewards/sales_aggregated_cart');

            $websiteId = $aggregatedCart->getWebsiteId();
            $customerGroupId = $aggregatedCart->getCustomerGroupId();

            $rules = Mage::getResourceModel('rewardspointsonly/rule')
                ->getRulesForValidation($websiteId, $customerGroupId);

            $found = false;

            foreach ($rules as $rule) {
                if ($rule->getConditions()->validate($itemOrProduct)) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                return true;
            } else {
                return false;
            }
        }
        
        return false;
    }

    /**
     * Number of Selectable Bundle Options Selections
     * @param Mage_Catalog_Model_Product $product
     * @return int
     */
    public function getNumberOfSelectableBundleOptions(Mage_Catalog_Model_Product $product)
    {
        if ($product->getTypeId() !== 'bundle') {
            return 1;
        }

        $options = $product->getTypeInstance(true)->getOptions($product);
        $selections = $product->getTypeInstance(true)->getSelectionsByIds(array(), $product);

        $selectableItems = 0;

        foreach ($options as $option) {
            if (
                $option->getType() === 'multi'
                || $option->getType() === 'checkbox'
            ) {
                foreach ($selections as $selection) {
                    if ($selection->getOptionId() !== $option->getOptionId()) {
                        continue;
                    }

                    $selectionPrice = 0.0;

                    if ((int) $product->getPriceType() === (int) Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
                        $selectionPrice = $selection->getSelectionPriceValue();
                    } else {
                        $selectionPrice = $selection->getPrice();
                    }

                    if ($selectionPrice > 0.00001) {
                        $selectableItems++;
                    }
                }
            } else {
                foreach ($selections as $selection) {
                    if ($selection->getOptionId() !== $option->getOptionId()) {
                        continue;
                    }

                    $selectionPrice = 0.0;

                    if ((int) $product->getPriceType() === (int) Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED) {
                        $selectionPrice = $selection->getSelectionPriceValue();
                    } else {
                        $selectionPrice = $selection->getPrice();
                    }

                    if ($selectionPrice > 0.00001) {
                        $selectableItems++;
                        break;
                    }
                }
            }
        }

        return $selectableItems;
    }

    public function getSelectionProduct(Mage_Catalog_Model_Product $parentProduct, Mage_Catalog_Model_Product $product)
    {
        if ($parentProduct->getTypeId() !== 'bundle') {
            return $product;
        }

        $options = $parentProduct->getTypeInstance(true)->getOptions($parentProduct);
        $selections = $parentProduct->getTypeInstance(true)->getSelectionsByIds(array(), $parentProduct);

        $foundSelection = $product;

        foreach ($options as $option) {
            $found = false;
            foreach ($selections as $selection) {
                if ($selection->getProductId() === $product->getId() && $selection->getParentProductId() === $parentProduct->getId()) {
                    $foundSelection = $selection;
                    $found = true;
                    break;
                }
            }

            if ($found) {
                break;
            }
        }

        return $foundSelection;
    }
}
