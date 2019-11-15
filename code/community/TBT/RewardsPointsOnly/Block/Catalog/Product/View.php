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
 * Frontend Price renderer for PointsOnly (EE fix)
 *
 * @category   TBT
 * @package    TBT_RewardsPointsOnly
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Block_Catalog_Product_View
    extends Mage_Catalog_Block_Product_View
{
    protected function _toHtml()
    {
        if (!Mage::helper('rewardspointsonly')->isPointsOnlyEnabled()) {
            return parent::_toHtml();
        }
        
        $taxHelper = Mage::helper('tax');
        
        $productPrice = $this->getProduct()->getPriceModel()->getBasePrice($this->getProduct());
        $productPrice = $taxHelper->getPrice($this->getProduct(), $productPrice, $taxHelper->priceIncludesTax());

        $pointsArr = Mage::getSingleton('rewardspointsonly/service_processRules')
            ->getPointsCost($this->getProduct(),null, null, $productPrice);

    	if (empty($pointsArr)) {
    		return parent::_toHtml();
    	}

        if (strpos($this->getTemplate(), "tierprices.phtml") !== false) {
    		return '';
    	}

        return parent::_toHtml();
    }
}