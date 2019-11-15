<?php

if (Mage::helper('rewards/version')->isBaseMageVersionAtLeast("1.7.0")) {
    class TBT_RewardsPointsOnly_Model_RuleAbstract extends Mage_Rule_Model_Abstract {
        /**
         * Get rule condition combine model instance
         *
         * @return Mage_SalesRule_Model_Rule_Condition_Combine
         */
        public function getConditionsInstance()
        {
            return Mage::getModel('catalogrule/rule_condition_combine');
        }

        /**
         * Get rule condition product combine model instance
         *
         * @return Mage_SalesRule_Model_Rule_Condition_Product_Combine
         */
        public function getActionsInstance()
        {
            return Mage::getModel('salesrule/rule_condition_product_combine');
        }
    }
} else {
    class TBT_RewardsPointsOnly_Model_RuleAbstract extends Mage_Rule_Model_Rule {

    }
}


class TBT_RewardsPointsOnly_Model_Rule extends TBT_RewardsPointsOnly_Model_RuleAbstract implements TBT_Rewards_Model_Migration_Importable
{
    const DEDUCT_POINTS_ACTION = 'deduct_points';
    const DEDUCT_BY_AMOUNT_SPENT_ACTION = 'deduct_by_amount_spent';

    const REWARDSPOINTsONLY_RULE_POINTS_AMOUNT = 'points_amount';
    const REWARDSPOINTsONLY_RULE_POINTS_AMOUNT_STEP = 'points_amount_step';
    const REWARDSPOINTsONLY_RULE_POINTS_USES = 'points_uses';
    const REWARDSPOINTsONLY_RULE_POINTS_QTY = 'points_qty';

    public function _construct()
    {
        parent::_construct();
        $this->_init('rewardspointsonly/rule');
    }

    protected function _beforeSave()
    {
        if (is_array($this->getData('website_ids'))) {
            $this->setData('website_ids', implode(',', $this->getData('website_ids')));
        }

        if (is_array($this->getData('customer_group_ids'))) {
            $this->setData('customer_group_ids', implode(',', $this->getData('customer_group_ids')));
        }

        parent::_beforeSave();
    }

    /**
     * Get rule condition combine model instance
     *
     * @return Mage_SalesRule_Model_Rule_Condition_Combine
     */
    public function getConditionsInstance()
    {
        return Mage::getModel('catalogrule/rule_condition_combine');
    }

    /**
     * Get rule condition product combine model instance
     *
     * @return Mage_SalesRule_Model_Rule_Condition_Product_Combine
     */
    public function getActionsInstance()
    {
        return Mage::getModel('salesrule/rule_condition_product_combine');
    }

    public function getRedemptionOptionArray()
    {
        return array(
            self::DEDUCT_POINTS_ACTION => Mage::helper('rewardspointsonly')->__('Spends X Points'),
            self::DEDUCT_BY_AMOUNT_SPENT_ACTION => Mage::helper('rewardspointsonly')->__('Spends X points for every Y dollar amount in price')
        );
    }

    public function getWebsiteIds()
    {
        $websiteIds = explode(',', $this->getData('website_ids'));

        return $websiteIds;
    }

    public function getCustomerGroupIds()
    {
        $customerGroupIds = explode(',', $this->getData('customer_group_ids'));

        return $customerGroupIds;
    }
    
    public function saveWithId()
    {
        $id = $this->getId();
        $exists  = Mage::getModel($this->_resourceName)->setId(null)->load($id)->getId();

        if (!$exists) {
            $this->setId(null);
        }

        $this->save();
        return $this;
    }
}

