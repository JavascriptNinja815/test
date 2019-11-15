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
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Observer_Adminhtml_Block_Output extends Varien_Object
{
    /**
     * Executed from the core_block_abstract_to_html_after event
     * @param Varien_Event $obj
     */
    public function afterOutput($obj)
    {
        $block = $obj->getEvent ()->getBlock ();
        $transport = $obj->getEvent ()->getTransport ();

        // Magento 1.3 and lower dont have this transport, so we can't do autointegration : (
        if(empty($transport)) {
            return $this;
        }

        if (Mage::getStoreConfigFlag('advanced/modules_disable_output/TBT_Rewards')) {
            return $this;
        }
        
        $this->appendPointsAdjustmentToCreditmemo($block, $transport);
        $this->appendPointsBalanceToAdminOrderInfo($block, $transport);
        $this->overwriteOrderCancelPopup($block, $transport);
        
        if ($block instanceof Mage_Adminhtml_Block_Dashboard_Sales) {
            $html = $transport->getHtml ();
            $rewardsDashboardHtml = $block->getParentBlock()->getChildHtml('rewards_dashboard_widget');
            if (!empty($rewardsDashboardHtml))
                $html .= $rewardsDashboardHtml;
                $transport->setHtml($html);
        }
        
        return $this;
    }
    
    /**
     * Appends some fields to the creditmemo form, to adjust points earned and spent on the order.
     * @param Mage_Adminhtml_Block_Sales_Order_Creditmemo_Totals $block
     * @param Varien_Object $transport
     * @return TBT_Rewards_Model_Observer_Block_Output
     */
    public function appendPointsAdjustmentToCreditmemo($block, $transport)
    {
        if (!($block instanceof Mage_Adminhtml_Block_Sales_Order_Creditmemo_Totals)) {
            return $this;
        }

        $html = $transport->getHtml();

        $stBlock = $block->getLayout()->createBlock('rewards/adminhtml_sales_order_creditmemo_points');
        $stBlock->setOrder($block->getOrder());
        $stHtml = $stBlock->toHtml();

        $html .= "<div class='divider'></div>";
        $html .= $stHtml;

        $transport->setHtml($html);

        return $this;
    }
    
    /**
     * Appends the customer's current points balance to the customer Account Information section
     * of the order view page, creditmemo page, etc.
     * @param Mage_Adminhtml_Block_Sales_Order_View_Info $block
     * @param Varien_Object $transport
     * @return TBT_Rewards_Model_Observer_Block_Output
     */
    public function appendPointsBalanceToAdminOrderInfo($block, $transport)
    {
        if (!($block instanceof Mage_Adminhtml_Block_Sales_Order_View_Info)) {
            return $this;
        }

        $html = $transport->getHtml();

        $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
        $customerId = $block->getOrder()->getCustomerId();
        $customer = Mage::getModel('rewards/customer')->load($customerId);
        $pointsBalance = $customer->getUsablePointsBalance($currencyId);

        $label = Mage::helper('rewards')->__("Customer Points Balance");
        $pointsString = Mage::getModel('rewards/points')->set($currencyId, $pointsBalance);

        $insert = "<td class=\"label rewards-balance-label\"><label>{$label}</label></td>
                <td class=\"value rewards-balance-value\"><strong>{$pointsString}</strong></td>$1";
        $needle = $this->_getEndOfAccountInformationHtml();
        $html = preg_replace($needle, $insert, $html, 1);

        $transport->setHtml($html);

        return $this;
    }
    
    /**
     * Overwrite Order Cancel Popup
     * @param Mage_Adminhtml_Block_Sales_Order_View $block
     * @param Varien_Object $transport
     * @return \TBT_Rewards_Model_Observer_Adminhtml_Block_Output
     */
    public function overwriteOrderCancelPopup($block, $transport)
    {
        if (!Mage::helper('rewards/config')->canAdjustPoints()) {
            return $this;
        }
        
        if (!($block instanceof Mage_Adminhtml_Block_Sales_Order_View)) {
            return $this;
        }

        $html = $transport->getHtml();

        $popup = $block->getLayout()->createBlock('rewards/adminhtml_sales_order_cancel_popup');
        $popup->setOrder($block->getOrder());
        $html .= $popup->toHtml();

        $transport->setHtml($html);

        return $this;
    }
    
    /**
     * Returns the HTML used to find the end of the customer Account Information section
     * of the order view page, creditmemo page, etc.  Used to append the customer balance.
     * TODO: this might be easier solved if we add a customer EAV attribute for points balance
     */
    protected function _getEndOfAccountInformationHtml()
    {
        return '/(<\/table>[\s]*<\/div>[\s]*<\/div>[\s]*<\/div>[\s]*<\/div>[\s]*<div class="clear"><\/div>[\s]*[\s]*<div class="box-left">[\s]*<!--Billing Address-->)/';
    }
}