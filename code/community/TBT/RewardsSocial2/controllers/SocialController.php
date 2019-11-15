<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 *      https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL:
 *      http://opensource.org/licenses/osl-3.0.php
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
 * @package    [TBT_RewardsSocial2]
 * @copyright  Copyright (c) 2015 Sweet Tooth Inc. (http://www.sweettoothrewards.com
 */

/**
 * RewardsSocial Social Controller
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_SocialController extends Mage_Core_Controller_Front_Action
{
    public function registerEventAction()
    {
        $action = $this->getRequest()->getParam('action');
        $helper = Mage::helper('rewardssocial2');
        
        $dataJson = $this->getRequest()->getParam('data');
        $data = json_decode($dataJson, true);

        $currentPageUrl = $this->getRequest()->getParam('current_page_url', false);
        
        $code = 204;
        $message = '';
        
        try {
            if (!$action) {
                throw new Exception($this->__('No action specified.'), 400);
            }
            
            if (!$helper->hasCustomerToReward()) {
                throw new Exception($this->__('You have to log in to earn points.'), 401);
            }

            if (!$helper->hasSocialRulesForAction($action)) {
                throw new Exception($this->__('There are no rules set up for this type of action.'), 204);
            }

            if (!Mage::getResourceModel('rewardssocial2/action')->isRequestIntervalValid($this->getCustomer()->getId())) {
                throw new Exception($this->__('Your social interactions are happening too frequently to be eligible for rewards. Please try again later.'), 429);
            }

            $checkLimitErrors = Mage::getSingleton('rewardssocial2/action')->validateRequestLimit($this->getCustomer()->getId());
            if ($checkLimitErrors) {
                throw new Exception($this->__($checkLimitErrors), 400);
            }

            $data['url'] = $currentPageUrl ?: $this->fetchAjaxRequestUrl();

            // ex: facebook_like => processFacebookLike
            $processor = 'process' . str_replace(' ', '', ucwords(str_replace('_', ' ', $action)));
            $this->$processor($data);

            // Save social request
            $actionModel = Mage::getModel('rewardssocial2/action')
                ->setCustomerId($this->getCustomer()->getId())
                ->setAction($action)
                ->genericExtraSetter($data)
                ->save();

            // Create the transfers
            Mage::getModel('rewardssocial2/transfer')->initiateTransfers($actionModel);
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $code = $e->getCode();
        }
        
        if ($code === 204 && !$message) {
            $message = $helper->getSuccessMessage($action);
            Mage::getSingleton('core/session')->addSuccess($message);
        }
        
        $this->getResponse()->setHeader('HTTP/1.0', $code, true);
        $this->getResponse()->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        $this->getResponse()->setBody($message);
        
        return $this;
    }

    /**
     * Action to respond with a Product URL, given it's id in the request parameter `product_id`
     * Response body will be a plain-text URL of product with HTTP 200 status if product found.
     * Response will return HTTP 404 status if product not found.
     * @return $this
     */
    public function fetchProductUrlAction()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $productUrl = Mage::helper('rewardssocial2')->getProductUrl($productId);

        if (empty($productUrl)) {
            $this->getResponse()->setHttpResponseCode(404);
            $this->getResponse()->setBody('Product not found.');

        } else {
            $this->getResponse()->setBody($productUrl);
        }

        return $this;
    }

    protected function processFacebookLike($data)
    {
        $url = $data['url'];
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception($this->__('You provided an invalid page key.'), 400);
        }

        if (Mage::getResourceModel('rewardssocial2/action')->wasAlreadyRewarded($this->getCustomer(), 'facebook_like', $url)) {
            throw new Exception($this->__('You already received points for liking this page.'), 400);
        }
    }
    
    protected function processFacebookShare($data)
    {
        $productId = $data['url'];
        
        if (!$productId) {
            throw new Exception($this->__('You provided an invalid product id.'), 400);
        }

        if (Mage::getResourceModel('rewardssocial2/action')->wasAlreadyRewarded($this->getCustomer(), 'facebook_share', $productId)) {
            throw new Exception($this->__('You already received points for sharing this page.'), 400);
        }
    }
    
    protected function processTwitterFollow($data)
    {
        if (Mage::getResourceModel('rewardssocial2/action')->wasAlreadyRewarded($this->getCustomer(), 'twitter_follow')) {
            throw new Exception($this->__("You've already been rewarded for following us on Twitter!"), 400);
        }
    }
    
    protected function processTwitterTweet($data)
    {
        $url = $data['url'];

        if (Mage::getResourceModel('rewardssocial2/action')->wasAlreadyRewarded($this->getCustomer(), 'twitter_tweet', $url)) {
            throw new Exception($this->__("You've already tweeted about this page."), 400);
        }
    }
    
    protected function processGooglePlusOne($data)
    {
        $url = $data['url'];

        if (Mage::getResourceModel('rewardssocial2/action')->wasAlreadyRewarded($this->getCustomer(), 'google_plusone', $url)) {
            throw new Exception($this->__("You've already +1'd this page."), 400);
        }
    }
    
    protected function processPinterestPin($data)
    {
        $url = $data['url'];

        if (Mage::getResourceModel('rewardssocial2/action')->wasAlreadyRewarded($this->getCustomer(), 'pinterest_pin', $url)) {
            throw new Exception($this->__("You've already pinned this product."), 400);
        }
    }
    
    protected function processFacebookSharePurchase($data)
    {
        $this->validatePurchaseShare($data);
        
        $search = array(
            'product' => $data['product'],
            'order' => $data['order']
        );
        
        if (Mage::getResourceModel('rewardssocial2/action')->wasAlreadyRewarded($this->getCustomer(), 'facebook_share_purchase', json_encode($search))) {
            throw new Exception($this->__("You've already been rewarded for sharing this purchase on facebook"), 400);
        }
    }
    
    protected function processTwitterTweetPurchase($data)
    {
        $this->validatePurchaseShare($data);
        
        $search = array(
            'product' => $data['product'],
            'order' => $data['order']
        );
        
        if (Mage::getResourceModel('rewardssocial2/action')->wasAlreadyRewarded($this->getCustomer(), 'twitter_tweet_purchase', json_encode($search))) {
            throw new Exception($this->__("You've already been rewarded for sharing this purchase on twitter"), 400);
        }
    }
    
    protected function processFacebookShareReferral($data)
    {
        return $this->abstractProcessReferralShare($data);
    }
    
    protected function processTwitterTweetReferral($data)
    {
        return $this->abstractProcessReferralShare($data);
    }
    
    protected function abstractProcessReferralShare($data)
    {
        $xRequestedWith = filter_input(INPUT_SERVER, 'HTTP_X_REQUESTED_WITH');
        
        if (!$xRequestedWith || $xRequestedWith != "XMLHttpRequest") {
            throw new Exception($this->__('Referral share link accessed from wrong endpoint!'), 400);
        }
    }
    
    protected function validatePurchaseShare($data)
    {
        if (!isset($data['product']) || !isset($data['order'])) {
            throw new Exception($this->__("Insufficient data."), 400);
        }
        
        $order = Mage::getModel('sales/order')->load($data['order'], 'increment_id');
        if (!$order->getId()) {
            throw new Exception($this->__("Invalid order ID."), 400);
        }
        
        if ($order->getCustomerId() != $this->getCustomer()->getId()) {
            throw new Exception($this->__("This order was placed by someone else."), 400);
        }
        
        $product = Mage::getModel('catalog/product')->load($data['product']);
        if (!$product->getId()) {
            throw new Exception($this->__("Invalid product ID."), 400);
        }
        
        $orderIncludesProduct = Mage::getResourceModel('sales/order_item_collection')
            ->addFieldToFilter('order_id', $order->getId())
            ->addFieldToFilter('product_id', $data['product'])
            ->getSize();
        
        if (!$orderIncludesProduct) {
            throw new Exception($this->__("The product is not part of this order."), 400);
        }
    }
    
    public function getCustomer()
    {
        return Mage::helper('rewardssocial2')->getCustomerForSocialAction();
    }
    
    public function fetchAjaxRequestUrl()
    {
        return filter_input(INPUT_SERVER, 'HTTP_REFERER');
    }
}
