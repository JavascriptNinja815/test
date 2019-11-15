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
 * Catalog Points Earning Predictions
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Block_Catalog_PredictPoints_Earning
    extends Mage_Core_Block_Template
{
    /**
     * Main Constructor
     */
    public function _construct()
    {
        parent::_construct();

        $aggregatedCart = Mage::getSingleton('rewards/sales_aggregated_cart');

        $this->setTemplate('rewards/catalog/predict_points/earnings.phtml');
        
        if (!$this->getCacheLifetime()) {
            $this->setCacheLifetime((strtotime('tomorrow') - time()));
        }

        $this->addData(
            array(
                'cache_tags' => array(
                    Mage_Catalog_Model_Product::CACHE_TAG,
                    TBT_Rewards_Model_Salesrule_Rule::REWARDS_SALESRULE_EARNING_CACHE_TAG
                ),
                'cache_key' => 'REWARDS_SALESRULE_EARNING_PREDICTION_NO_BUYREQUEST'
                    . '_' . $this->getProduct()->getId()
                    . '_' . $aggregatedCart->getWebsiteId()
                    . '_' . $aggregatedCart->getCustomerGroupId()
            )
        );
    }

    /**
     * Prepare Earning String Prediction
     * @return string
     */
    protected function getEarningsString()
    {
        $predictionService = Mage::getSingleton('rewards/salesrule_predictPoints');

        if (!$this->getProduct() instanceof Mage_Catalog_Model_Product) {
            return '';
        }

        $earningsPointsData = $predictionService->getEarningsForProduct($this->getProduct());

        $pointsEarningsSum = 0;

        foreach ($earningsPointsData as $earningPointsRow) {
            $pointsEarningsSum += $earningPointsRow['amount'];
        }

        if ($pointsEarningsSum === 0) {
            return '';
        }

        $currencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();

        return Mage::helper('rewards')->getPointsString(array($currencyId => $pointsEarningsSum));
    }
}
