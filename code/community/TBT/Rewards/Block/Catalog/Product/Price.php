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
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Frontend Price renderer for Rewards Predictions
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Catalog_Product_Price extends Mage_Catalog_Block_Product_Price
{
    /**
     * Block Html generator
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!Mage::helper('rewards/version')->isBaseMageVersionAtLeast("1.7.0")) {
            return parent::_toHtml();
        }

        if ($this->getRewardsRestrictPredictions()) {
            return parent::_toHtml();
        }

        if (
            strpos($this->getTemplate(), "price.phtml") === false
        ) {
            return parent::_toHtml();
        }

        if (strpos($this->getIdSuffix(), "_clone") !== false) {
            return parent::_toHtml();
        }

        $predictionsBlock = $this->getLayout()->createBlock('rewards/catalog_predictPoints', 'rewards_catalog_predictions');

        if (!$predictionsBlock) {
            return parent::_toHtml();
        }

        $predictionsBlock->setProduct($this->getProduct());

        return parent::_toHtml() . $predictionsBlock->toHtml();
    }
}
