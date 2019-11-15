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
 * Sales Rules Points Predictions for Spendings
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Salesrule_PredictPoints_Spendings
    extends Varien_Object
{
    /**
     * Rewards Shopping Cart Rules
     * 
     * @var array
     */
    protected $_rules = array();

    /**
     * Prepare Spendings Data for Product
     * 
     * @param Mage_Catalog_Model_Product $product
     * @param int $websiteId
     * @param int $customerGroupId
     * @param TBT_Rewards_Model_Customer|null $customer
     * @param Mage_Core_Model_Store $store
     * @param array $buyRequest
     * @return array
     */
    public function getSpendingsForProduct(
        Mage_Catalog_Model_Product $product,
        $websiteId, $customerGroupId,
        $customer,
        Mage_Core_Model_Store $store,
        $buyRequest = null
    )
    {
        $pointsData = array();

        return $pointsData;
    }

    /**
     * Prepare Rewards Shopping Cart Spending Rules
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @return array
     */
    protected function _getRules($websiteId, $customerGroupId)
    {
        if (empty($this->_rules[$websiteId][$customerGroupId])) {
            $ruleRedemptionActions = Mage::getModel('rewards/salesrule_actions')->getRedemptionActions();

            $rulesCollection = Mage::getModel('salesrule/rule')->getCollection();
            $rulesCollection->setFlag('validation_filter', false)
                ->setValidationFilter($websiteId, $customerGroupId)
                ->addFieldToFilter('points_action', array('in' => $ruleRedemptionActions))
                ->addFieldToFilter('points_action', array('neq' => ''));
            $rulesCollection->load();

            $this->_rules[$websiteId][$customerGroupId] = $rulesCollection->getItems();
        }

        return $this->_rules[$websiteId][$customerGroupId];
    }
}
