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
 * This class is used as a controller to process rewards request actions
 * @package     TBT_Rewards
 * @subpackage  controllers
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Adminhtml_Sales_Order_Create_PointsController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * Acl check for admin
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('rewards');
    }
    
    /**
     * Action used to configure catalogrule rewards in admin order creation
     * @return null
     */
    public function configureAction()
    {
        $productId = $this->getRequest()->getParam('id');
        $isProductConfigured = $this->getRequest()->getParam('is_product_configured');
        $configuredPoints = $this->getRequest()->getParam('configured_points');
        $buyRequest = $this->_prepareProductBuyRequest($this->getRequest()->getParams());

        if (!(bool)$isProductConfigured) {
            return $this->_setErrorOutput(
                Mage::helper('rewards')->__("Please configure product options first!")
            );
        }
        
        $currentCustomer = Mage::getSingleton('rewards/sales_aggregated_cart')->getCustomer();
        $customerUsablePoints = $currentCustomer ? $currentCustomer->getUsablePoints() : 0;
        $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();

        if ($customerUsablePoints[$currencyId] - $configuredPoints < 1) {
            return $this->_setErrorOutput(
                Mage::helper('rewards')->__("Customer doesn't have enough points or all points are already configured to be spent!")
            );
        }
        
        $product = Mage::getModel('catalog/product')
            ->setStoreId(Mage::getSingleton('rewards/sales_aggregated_cart')->getQuote()->getStoreId())
            ->load($productId);

        $product->getTypeInstance(false)->prepareForCartAdvanced($buyRequest, $product);

        Mage::register ( 'product', $product );
        
        if (!$product || !$product->getId()) {
            return $this->_setErrorOutput(
                Mage::helper('rewards')->__('Cannot configure rewards due to an unexpected behavior!')
            );
        }
        
        $pointBlock = $this->getLayout()
            ->createBlock('rewards/integrated_product_view_points', "rewards.integrated.product.view.points")
            ->setTemplate("rewards/sales/order/create/search/item/points_configure.phtml")
            ;
        
        $earnBlock = $this->getLayout()
            ->createBlock('rewards/product_view_points_earned', "rewards.product.view.points.earned")
            ->setTemplate("rewards/sales/order/create/search/item/points_configure_earned.phtml")
            ;

        $pointsRedeemedBlock = $this->getLayout()
            ->createBlock('rewards/product_view_points_redeemed', "points_redeemed")
            ->setTemplate("rewards/sales/order/create/search/item/points_configure_redeemed.phtml")
            ->setForcedDisplayFlag(true);

        $pointsSliderBlock = $this->getLayout()
            ->createBlock('rewards/points_slider', "rewards.product.view.points.slider")
            ->setTemplate("rewards/sales/order/create/search/item/points_configure_slider.phtml");

        $pointsSliderJsBlock = $this->getLayout()
            ->createBlock('rewards/points_slider', "rewards.product.view.points.slider.js")
            ->setTemplate("rewards/sales/order/create/search/item/points_configure_slider_js.phtml");

        $pointsRedeemedBlock->setChild("points_slider", $pointsSliderBlock);
        $pointsRedeemedBlock->setChild("points_slider_js", $pointsSliderJsBlock);

        $pointBlock->setChild("points_earned", $earnBlock);
        $pointBlock->setChild("points_redeemed", $pointsRedeemedBlock);

        $pointHtml = $pointBlock
            ->unsetData('cache_lifetime')
            ->unsetData('cache_tags')
            ->toHtml();
        
        $this->getResponse()->setBody($pointHtml);
        return;
    }
    
    /**
     * Set Error Output Headers in the Response
     * @param string $errorMessage
     * @return null
     */
    protected function _setErrorOutput($errorMessage)
    {
        $errorBlock = $this->getLayout()
            ->createBlock('adminhtml/template', "rewards-points-error")
            ->setTemplate("rewards/sales/order/create/search/item/error.phtml");
        
        $errorBlock->setErrorMessage($errorMessage);
        
        $this->getResponse()->setBody($errorBlock->toHtml());
        return;
    }
    
    /**
     * Map Catalog Points for cart items rewards info section
     * @return \TBT_Rewards_Adminhtml_Sales_Order_Create_PointsController
     */
    public function mapCatalogPointsAction()
    {
        $output = array(
            'error' => false,
            'errorMessage' => '',
            'result' => array()
        );
        
        $quote = Mage::getSingleton('rewards/sales_aggregated_cart')->getQuote();

        $quote->setTotalsCollectedFlag(false)->collectTotals()->save();
        Mage::getSingleton('rewards/catalogrule_service_processRules')
            ->refactorRedemptions($quote->getAllItems());
        
        if (!$quote || !$quote->getId()) {
            $output['error'] = true;
            $output['errorMessage'] = Mage::helper('rewards')->__(
                'An unexpected error has occured. Please try again later!'
            );
            
            $this->getResponse()->setHeader('Content-Type', 'application/json', true);
            $this->getResponse()->setBody(Zend_Json::encode($output));
            return $this;
        }
        
        foreach ($quote->getAllVisibleItems() as $item) {
            $itemRewardsInfoService = Mage::getModel('rewards/sales_service_item_info',$item);
            
            $rewardsInfoBlock = $this->getLayout()
                ->createBlock('adminhtml/template', 'cart_item_rewards_info_block')
                ->setTemplate('rewards/sales/order/create/cart/item/rewards_info.phtml')
                ->setItemRewardsInfoService($itemRewardsInfoService);
            
            $output['result'][$item->getId()] = $rewardsInfoBlock->toHtml();
        }
        
        $output['error'] = false;
        $output['errorMessage'] = '';
        
        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(Zend_Json::encode($output));
        return $this;
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

        if (!$request->hasQty()) {
            $request->setQty(1);
        }

        return $request;
    }
}