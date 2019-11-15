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
 * Aw Onestepcheckout Redeem Controller
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Awonestepcheckout_RedeemController extends Mage_Core_Controller_Front_Action
{
    /**
     * Recreate checkout cart summary blocks and return them for the ajax request
     */
    public function fetchAjaxTotalBlocksAction()
    {
        Mage::getSingleton('checkout/cart')->getQuote()->collectTotals();

        $this->loadLayout('aw_onestepcheckout_index_index');
        $checkoutReviewBlock = $this->getLayout()->getBlock('aw_onestepcheckout.onestep.form.review.cart');
        
        $result['error'] = false;
        $result['errorMessage'] = '';
        
        if (!$checkoutReviewBlock) {
            $result['error'] = true;
            $result['errorMessage'] = Mage::helper('rewards')->__(
                'Review block is not present. Please refresh the page to see the correct results!'
            );
            $this->buildOutputResponse($result);
            return $this;
        }

        $checkoutReviewBlock->setFrameTags(null,null);
        $checkoutReviewBlockHtml = $checkoutReviewBlock->toHtml();
        
        $result['checkoutReviewHtml'] = $checkoutReviewBlockHtml;
        $result['grandTotal'] = Mage::helper('core')->formatPrice(
            Mage::getSingleton('checkout/session')->getQuote()->getGrandTotal(), false
        );
        
        $this->buildOutputResponse($result);
        return $this;
    }
    
    protected function buildOutputResponse(array $result)
    {
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}