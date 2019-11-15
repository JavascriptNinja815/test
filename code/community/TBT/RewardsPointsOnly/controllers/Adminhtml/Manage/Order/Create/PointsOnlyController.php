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
 * @package    [TBT_RewardsPointsOnly]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

include_once(Mage::getModuleDir('controllers', 'TBT_Rewards').DS.'Admin'.DS.'AbstractController.php');

/**
 * This class is used as a controller to process points only for admin order creation
 * @package     TBT_RewardsPointsOnly
 * @subpackage  controllers
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Adminhtml_Manage_Order_Create_PointsOnlyController
    extends TBT_Rewards_Admin_AbstractController
{
    /**
     * Action used to generate a map of pointsonly prices for all valid cart items
     * @return \TBT_RewardsPointsOnly_Adminhtml_Manage_Order_Create_PointsOnlyController
     */
    public function mapCartPointsOnlyAction()
    {
        $output = array(
            'error' => true,
            'errorMessage' => '',
            'result' => array()
        );

        $quote = Mage::getSingleton('rewards/sales_aggregated_cart')->getQuote();

        foreach ($quote->getAllVisibleItems() as $item) {
            $pointsData = Mage::getSingleton('rewardspointsonly/service_processRules')
                ->getItemPointsCost($item);

            if (!empty($pointsData)) {
                $output['result'][$item->getId()]['pointsonly_unit_price'] = $pointsData['points_unit_price_string'];
                $output['result'][$item->getId()]['pointsonly_row_total'] = $pointsData['points_row_total_price_string'];
            }
        }

        $output['error'] = false;
        $output['errorMessage'] = '';

        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(Zend_Json::encode($output));
        
        return $this;
    }

    /**
     * Update Points Only Product Search Product Points Price
     */
    public function configurePointsOnlyAction()
    {
        $response = array();

        $productId = $this->getRequest()->getParam('id');

        $response['product_id'] = $productId;

        $buyRequest = $this->_prepareProductBuyRequest($this->getRequest()->getParams());

        $product = Mage::getModel('catalog/product')
            ->setStoreId(Mage::getSingleton('rewards/sales_aggregated_cart')->getQuote()->getStoreId())
            ->load($productId);

        $product->getTypeInstance(false)->prepareForCartAdvanced($buyRequest, $product);

        list($productPriceExclTax, $productPriceInclTax) = Mage::getModel('rewards/service_catalog_product_price')
            ->getPricesForProduct($product, $buyRequest->toArray());

        $aggregatedCart = Mage::getSingleton('rewards/sales_aggregated_cart');

        if (Mage::helper('tax')->priceIncludesTax($aggregatedCart->getStoreId())) {
            $productPrice = $productPriceInclTax;
        } else {
            $productPrice = $productPriceExclTax;
        }

        $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
            ->getPointsCost($product, null, null, $productPrice);

        if(!empty($pointsArr)) {
            $response['rewardspointsonly_points_price_value'] = $pointsArr['points_min_value'];
            $response['rewardspointsonly_points_price_str'] = $pointsArr['points_min_str'];
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(Zend_Json::encode($response));
    }

    /**
     * Prepare BuyRequest from request params
     * @param Varien_Object $requestParams
     * @return \Varien_Object
     */
    private function _prepareProductBuyRequest($requestParams)
    {
        if ($requestParams instanceof Varien_Object) {
            $request = $requestParams;
        } elseif (is_numeric($requestParams)) {
            $request = new Varien_Object(array('qty' => $requestParams));
        } else {
            $request = new Varien_Object($requestParams);
        }

        $request->setQty(1);

        return $request;
    }
}
