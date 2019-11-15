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
 * Checkout Cart Bundle Item PointsOnly Renderer
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Checkout_Cart_Item_Renderer_Bundle
    extends TBT_RewardsPointsOnly_Block_Checkout_Cart_Item_Renderer
{
    /**
     * Get bundled selections (slections-products collection)
     *
     * Returns array of options objects.
     * Each option object will contain array of selections objects
     *
     * @return array
     */
    protected function _getBundleOptions($useCache = true)
    {
        $serializerHelper = Mage::helper('rewards/serializer');
        $options = array();
        $typeInstance = $this->getProduct()->getTypeInstance(true);
        $optionsQuoteItemOption = $this->getItem()->getOptionByCode('bundle_option_ids');
        $bundleOptionsIds = $serializerHelper->unserializeData($optionsQuoteItemOption->getValue());

        if ($bundleOptionsIds) {
            /**
             * @var Mage_Bundle_Model_Mysql4_Option_Collection
             */
            $optionsCollection = $typeInstance->getOptionsByIds($bundleOptionsIds, $this->getProduct());

            /**
             * get and add bundle selections collection
             */
            $selectionsQuoteItemOption = $this->getItem()->getOptionByCode('bundle_selection_ids');

            $selectionsCollection = $typeInstance->getSelectionsByIds(
                $serializerHelper->unserializeData($selectionsQuoteItemOption->getValue()), 
                $this->getProduct()
            );

            $bundleOptions = $optionsCollection->appendSelections($selectionsCollection, true);
            foreach ($bundleOptions as $bundleOption) {
                if ($bundleOption->getSelections()) {
                    $option = array('label' => $bundleOption->getTitle(), "value" => array());
                    $bundleSelections = $bundleOption->getSelections();

                    foreach ($bundleSelections as $bundleSelection) {
                        $bundleOptionPrice = $this->_getSelectionFinalPrice($bundleSelection);

                        if (!Mage::helper('rewardspointsonly')->isPointsOnlyEnabled()) {
                            $pointsArr = array();
                        } else {
                            $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
                                ->getPointsCost($this->getProduct(), null, null, $bundleOptionPrice);
                        }

                        if (!empty($pointsArr)) {
                            $option['value'][] = $this->_getSelectionQty($bundleSelection->getSelectionId())
                             . ' x ' . $this->htmlEscape($bundleSelection->getName())
                             . ' ' . $pointsArr['points_min_str'];
                        } else {

                            $option['value'][] = $this->_getSelectionQty($bundleSelection->getSelectionId())
                                . ' x ' . $this->htmlEscape($bundleSelection->getName())
                                . ' ' . Mage::helper('core')->currency($bundleOptionPrice);
                        }
                    }

                    $options[] = $option;
                }
            }
        }

        return $options;
    }

    /**
     * Obtain final price of selection in a bundle product
     *
     * @param Mage_Catalog_Model_Product $selectionProduct
     * @return decimal
     */
    protected function _getSelectionFinalPrice($selectionProduct)
    {
        $bundleProduct = $this->getProduct();

        $selectionPrice = $bundleProduct->getPriceModel()->getSelectionFinalPrice(
            $bundleProduct, $selectionProduct, $this->getQty(),
            $this->_getSelectionQty($selectionProduct->getSelectionId())
        );

        $store = Mage::app()->getStore($this->getItem()->getStoreId());

        $taxCalculationModel = Mage::getModel('tax/calculation');
        $taxRequest = $taxCalculationModel->getRateRequest(
            $this->getItem()->getQuote()->getShippingAddress(),
            $this->getItem()->getQuote()->getBillingAddress(),
            $this->getItem()->getQuote()->getCustomerTaxClassId(),
            $store
        );

        $productTaxClassId = $selectionProduct->getTaxClassId();
        $taxRequest->setProductClassId($productTaxClassId);

        $taxPercent = $taxCalculationModel->getRate($taxRequest);

        if (Mage::helper('tax')->priceIncludesTax()) {
            $productPrice = $selectionPrice;
        } else {
            $productPrice = $selectionPrice * (1 + $taxPercent / 100);
        }

        return $productPrice;
    }

    /**
     * Get selection quantity
     *
     * @param int $selectionId
     * @return decimal
     */
    protected function _getSelectionQty($selectionId)
    {
        if ($selectionQty = $this->getProduct()->getCustomOption('selection_qty_' . $selectionId)) {
            return $selectionQty->getValue ();
        }

        return 0;
    }

    /**
     * Get list of all otions for product
     *
     * @return array
     */
    public function getOptionList()
    {
        return array_merge($this->_getBundleOptions(), parent::getOptionList());
    }
}
