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
 * @package    TBT_RewardsCoreSpending
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsCoreSpending_Model_Observer_Block_Output extends Varien_Object
{

    /**
     * Executed from the core_block_abstract_to_html_after event
     * @param Varien_Event $obj
     */
    public function afterOutput($obj)
    {
        $block = $obj->getEvent ()->getBlock ();
        $transport = $obj->getEvent ()->getTransport ();

        if (Mage::getStoreConfigFlag('advanced/modules_disable_output/TBT_RewardsCoreSpending')) {
            return $this;
        }

        $this->appendRedemptionInBackend($block, $transport);

        return $this;
    }

    /**
     * @param Mage_Core_Block_Abstract $block
     * @return self
     */
    public function appendRedemptionInBackend($block, $transport)
    {
        if (!($block instanceof Mage_Adminhtml_Block_Sales_Order_Create_Coupons)) {
            return $this;
        }

        // This was causing a nesting error if it was forced to happen deep inside the block tree, so get it out of the way now.
        $this->_getRewardsSession()->refreshSessionCustomer();

        $html = $transport->getHtml();
        $rewardsRedemptionBlock = $block->getChild('rewards_redemption');

        if ($rewardsRedemptionBlock) {
            $html .= $rewardsRedemptionBlock->toHtml();
        }
        
        $transport->setHtml($html);

        return $this;
    }

    /**
     * @return TBT_Rewards_Model_Session
     */
    protected function _getRewardsSession()
    {
        return Mage::getSingleton('rewards/session');
    }
}
