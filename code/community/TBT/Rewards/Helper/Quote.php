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
 * This class is used as helper for quote
 * @package     TBT_Rewards
 * @subpackage  Helper
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Helper_Quote extends Mage_Core_Helper_Abstract
{
    /**
     * Clones a sales/quote_item and then refills the fields that are automatically cleared
     * when that model gets cloned.  Performs some weird cloning logic on the item's parent as well,
     * because if we don't clone the parent, it will end up with double the children.
     * @depends compareTwoItems
     * @param Mage_Sales_Model_Quote_Item_Abstract $item
     *
     * @return Mage_Sales_Model_Quote_Item_Abstract
     */
    public function cloneQuoteItem($item)
    {
        $clone = clone $item;

        if ($clone instanceof Mage_Sales_Model_Quote_Item_Abstract) {
            // annoyingly, a bunch of values are unset after the item gets cloned.  let's revert that.
            $clone->setId($item->getId())
                ->setQuote($item->getQuote());

            // if the original item had a parent, we need it too
            if ($item->getParentItem()) {
                // we need to clone it though, otherwise we'll double-up its children when we call setParentItem()
                // because that implicitly calls setChild() on it.  what a pain.
                $newParent = clone $item->getParentItem();
                $siblings  = $item->getParentItem()->getChildren();
                if (is_array($siblings)) {
                    // just in case any of our code wants the parent to have all its children, let's add them
                    // (cloning the parent item clears its children)
                    // let's also try to keep the same order of children, just in case
                    foreach ($siblings as $sibling) {
                        // checks to see if this sibling is actually the current item
                        if ($this->compareTwoItems($sibling, $item)) {
                            $clone->setParentItem($newParent);
                        } else {
                            $newParent->addChild($sibling);
                        }
                    }
                }
            }

            // grab all the children from the original item and add them back
            $children = $item->getChildren();
            if (is_array($children)) {
                foreach ($children as $child) {
                    $clone->addChild($child);
                }
            }

            // grab all the "messages" from the original item and add them back
            $messages = $item->getMessage(false);
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    $clone->addMessage($message);
                }
            }
        }

        return $clone;
    }
    
    public function compareTwoItems($itemOne, $itemTwo)
    {
        if ($itemOne->getProductId() != $itemTwo->getProductId()) {
            return false;
        }
        foreach ($itemOne->getOptions() as $option) {
            if (
                ($option->getCode() == 'info_buyRequest')
                && !$itemTwo->getProduct()->hasCustomOptions()
            ) {
                continue;
            }
            
            if ($itemOption = $itemTwo->getOptionByCode($option->getCode())) {
                $itemOptionValue = $itemOption->getValue();
                $optionValue = $option->getValue();

                // dispose of some options params, that can cramp comparing of arrays
                if (is_string($itemOptionValue) && is_string($optionValue)) {
                    $serializerHelper = Mage::helper('rewards/serializer');
                    $_itemOptionValue = $serializerHelper->unserializeData($itemOptionValue);
                    $_optionValue = $serializerHelper->unserializeData($optionValue);
                    if (is_array($_itemOptionValue) && is_array($_optionValue)) {
                        $itemOptionValue = $_itemOptionValue;
                        $optionValue = $_optionValue;
                        // looks like it does not break bundle selection qty
                        unset($itemOptionValue['qty'], $itemOptionValue['uenc']);
                        unset($optionValue['qty'], $optionValue['uenc']);
                    }
                }

                if ($itemOptionValue != $optionValue) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }
}