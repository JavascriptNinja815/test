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
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Facade Main Service used to trigger processes for Points Only
 * @package     TBT_RewardsPointsOnly
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsPointsOnly_Model_Service_ProcessRules extends Varien_Object
{
    /**
     * Apply Points Only Model
     *
     * @var TBT_RewardsPointsOnly_Model_Service_ProcessRules_Apply
     */
    protected $_applyModel;

    /**
     * Remove Points Only Model
     *
     * @var TBT_RewardsPointsOnly_Model_Service_ProcessRules_Remove
     */
    protected $_removeModel;

    /**
     * Points Only Price Prediction Model
     *
     * @var TBT_RewardsPointsOnly_Model_Service_ProcessRules_PointsCost
     */
    protected $_pointsCostModel;

    /**
     * Points Only Rules Getter Model
     * @var TBT_RewardsPointsOnly_Model_Service_ProcessRules_Rules
     */
    protected $_rulesModel;

    /**
     * Main Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_applyModel = Mage::getModel('rewardspointsonly/service_processRules_apply');
        $this->_removeModel = Mage::getModel('rewardspointsonly/service_processRules_remove');
        $this->_pointsCostModel = Mage::getModel('rewardspointsonly/service_processRules_pointsCost');
        $this->_rulesModel = Mage::getSingleton('rewardspointsonly/service_processRules_rules');
    }

    /**
     * Validate and Apply Points Only
     * 
     * @param Mage_Sales_Model_Quote $quote
     * @return \TBT_RewardsPointsOnly_Model_Service_ProcessRules
     */
    public function validateAndApplyPointsOnItems($quote)
    {
        if (!$quote || !$quote->getId()) {
            return $this;
        }

        if (Mage::helper('rewardspointsonly')->isPointsOnlyEnabled()) {
            $this->_applyModel->applyPoints($quote);
        } else {
            $this->_removeModel->removePoints($quote);
        }

        return $this;
    }

    /**
     * Predict Points Only Price Cost for Catalog Product
     * 
     * @param Mage_Catalog_Model_Product $product
     * @param int|null $websiteId
     * @param int|null $customerGroupId
     * @param float|null $prefPrice
     * @return array
     */
    public function getPointsCost(Mage_Catalog_Model_Product $product, $websiteId = null, $customerGroupId = null, $prefPrice = null)
    {
        if (!Mage::helper('rewardspointsonly')->isPointsOnlyEnabled()) {
            return array();
        }

        $aggregatedCart = Mage::getSingleton('rewards/sales_aggregated_cart');

        if (!$websiteId) {
            $websiteId = $aggregatedCart->getWebsiteId();
        }

        if (!$customerGroupId) {
            $customerGroupId = $aggregatedCart->getCustomerGroupId();
        }

        if (!$product || !$product->getId()) {
            return array();
        }

        return $this->_pointsCostModel->getPointsCost($product, $websiteId, $customerGroupId, $prefPrice);
    }

    /**
     * Predict Points Only Price for Item
     * 
     * @param mixed $item
     * @param int|float $qty
     * @return array
     */
    public function getItemPointsCost($item, $qty = null)
    {
        return $this->_pointsCostModel->getItemPointsCost($item, $qty);
    }

    /**
     * Points Only Rules
     * @param int $websiteId
     * @param int $customerGroupId
     * @return array
     */
    public function getRules($websiteId, $customerGroupId)
    {
        return $this->_rulesModel->getRules($websiteId, $customerGroupId);
    }
}
