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
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales Rule Rule
 *
 * TODO: Add REWARDS getResourceCollection functionality to this model and the Rewards Catalogrule model
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Salesrule_Rule extends Mage_SalesRule_Model_Rule implements TBT_Rewards_Model_Migration_Importable {
    const POINTS_CURRENCY_ID = 'points_currency_id';
    const POINTS_AMT = 'points_amt';
    const POINTS_EFFECT = 'effect';
    const POINTS_RULE_ID = 'rule_id';
    const POINTS_APPLICABLE_QTY = 'applicable_qty';
    /**
     * This number of times the customer is using the redemption rule
     * to spend points and earn a discount.
     * For example, $1 off for every 25 points spent => # times 25 points is spent
     * is the 'uses' of that rule.
     *
     */
    const POINTS_USES = 'uses';

    const REWARDS_SALESRULE_EARNING_CACHE_TAG = 'rewards_salesrule_earning';

    const REWARDS_SALESRULE_SPENDING_CACHE_TAG = 'rewards_salesrule_spending';

    /**
     * Loads in a salesrule and returns a points salesrule
     *
     * @param Mage_SalesRule_Model_Rule $salesrule
     * @return TBT_Rewards_Model_Salesrule_Rule
     */
    public static function wrap(Mage_SalesRule_Model_Rule $salesrule) {
        $pointsrule = Mage::getModel ( 'rewards/salesrule_rule' )->setData ( $salesrule->getData () )->setId ( $salesrule->getId () );
        return $pointsrule;
    }

    /**
     * Returns true if this a redemption rule
     *
     * @return boolean
     */
    public function isRedemptionRule() {
        $ruleActionSing = Mage::getSingleton ( 'rewards/salesrule_actions' );
        return $ruleActionSing->isRedemptionAction ( $this->getPointsAction () );
    }
    /**
     * Returns true if this a distribution rule
     *
     * @return boolean
     */
    public function isDistributionRule() {
        $ruleActionSing = Mage::getSingleton ( 'rewards/salesrule_actions' );
        return $ruleActionSing->isDistributionAction ( $this->getPointsAction () );
    }
    /**
     * Returns the rule time id
     *
     * @return int
     */
    public function getRuleTypeId() {
        $ruleActionSing = Mage::getSingleton ( 'rewards/salesrule_actions' );
        return $ruleActionSing->getRuleTypeId ( $this->getPointsAction () );
    }

    /**
     * Fetches a list of IDs for all SALESRULE rules that
     * have a points action
     *
     * @return array
     */
    public function getPointsRuleIds() {
        $ids = Mage::getModel ( 'salesrule/rule' )->getCollection ()->addFieldToFilter ( "points_action", array ('neq' => '' ) )->getAllIds ();
        return $ids;
    }

    /**
     * True if this is a valid points salesrule
     *
     * @return boolean
     */
    public function isPointsRule() {
        $paction = $this->getPointsAction ();
        $valid = ! empty ( $paction );
        return $valid;
    }

    /**
     * True if this is a valid points salesrule
     * @param [$onle_active=false] : select only active rules
     * @return TBT_Rewards_Model_Mysql4_Salesrule_Rule_Collection
     */
    public function getPointsRuleCollection($onle_active = false) {
        $catalogruleActionsSingleton = Mage::getSingleton ( 'rewards/salesrule_actions' );
        $allowedActions = array ();
        if ($this->_getTypeHelper ()->isDistribution ( $this->getRuleTypeId () )) { // is a distribution
            $allowedActions = $catalogruleActionsSingleton->getDistributionActions ();
        } else {
            $allowedActions = $catalogruleActionsSingleton->getRedemptionActions ();
        }
        $collection = Mage::getModel ( 'rewards/salesrule_rule' )->getResourceCollection ()->addFieldToFilter ( "points_action", array ('IN' => $allowedActions ) );
        if ($onle_active) {
            $collection = $collection->addFieldToFilter ( "is_active", '1' );
        }
        return $collection;
    }

    /**
     * Fetches the rule type helper;
     * @return TBT_Rewards_Helper_Rule_Type
     */
    public function _getTypeHelper() {
        return Mage::helper ( 'rewards/rule_type' );
    }

    /**
     * Forcefully Save object data even if ID does not exist
         * Used for migrating data and ST campaigns.
     *
     * @return Mage_Core_Model_Abstract
     */
    public function saveWithId() {
        $real_id = $this->getId ();
        $exists = Mage::getModel ( $this->_resourceName )->setId ( null )->load ( $real_id )->getId ();

        if (! $exists) {
            $this->setId ( null );
        }

                $this->save();

        return $this;
    }

    /**
         * Check rule date integrity
         * @nelkaake Added on Wednesday October 6, 2010:
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave() {
        if (( int ) $this->getPointsAmount () === 0) {
            throw new Exception ( Mage::helper ( 'rewards' )->__ ( "The Points Amount (X) field must be greater than zero (0).  If you don't want customers to redeem any points, why are you making shopping cart points redemption rule?" ) );
        }

        /**
         * Support for other integrations that are checking for simple action index that does not exist
         */
        if (!$this->getSimpleAction()) {
            $this->setSimpleAction(null);
        }

        return parent::_beforeSave ();
    }
    
    /**
     * Track rule data
     */
    public function _afterSave()
    {
        Mage::helper('rewards/tracking')->sendRuleData('shopping cart', $this);
        return parent::_afterSave();
    }

    /**
     * @return boolean true if this rule's discount depends on the amount of points spent and the amount points spent is specified by the user.
     */
    public function isVariablePointsRule() {
        if ($this->getPointsAction () == TBT_Rewards_Model_Salesrule_Actions::ACTION_DISCOUNT_BY_POINTS_SPENT) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Unserializes conditions.
     *
     * @return self
     */
    public function unserializeConditions()
    {
        // Load rule conditions if it is applicable
        if (!$this->hasConditionsSerialized()) {
            return $this;
        }

        $conditions = $this->getConditionsSerialized();
        if (!empty($conditions)) {
            $conditions = Mage::helper('rewards/serializer')->unserializeData($conditions);
            if (is_array($conditions) && !empty($conditions)) {
                $this->getConditions()->loadArray($conditions);
            }
        }
        $this->unsConditionsSerialized();

        return $this;
    }

    /**
     * Unserializes actions.
     *
     * @return self
     */
    public function unserializeActions()
    {
        // Load rule actions if it is applicable
        if (!$this->hasActionsSerialized()) {
            return $this;
        }

        $actions = $this->getActionsSerialized();
        if (!empty($actions)) {
            $actions = Mage::helper('rewards/serializer')->unserializeData($actions);
            if (is_array($actions) && !empty($actions)) {
                $this->getActions()->loadArray($actions);
            }
        }
        $this->unsActionsSerialized();

        return $this;
    }

    /**
     * Clean Model Cache
     * @return \TBT_Rewards_Model_Salesrule_Rule
     */
    public function cleanModelCache()
    {
        $tags = $this->getCacheTagsSpecificForRuleType();

        if ($tags !== false) {
            Mage::app()->cleanCache($tags);
        }

        return $this;
    }

    /**
     * Prepare Cache Tags Specific for Rule Type
     * @return boolean|array
     */
    protected function getCacheTagsSpecificForRuleType()
    {
        $tags = $this->getCacheTags();

        if ($this->getRuleTypeId() === TBT_Rewards_Helper_Rule_Type::REDEMPTION) {
            $tags[] = self::REWARDS_SALESRULE_SPENDING_CACHE_TAG;
            if ($this->getId()) {
                $tags[] = self::REWARDS_SALESRULE_SPENDING_CACHE_TAG . '_' . $this->getId();
            }
        }

        if ($this->getRuleTypeId() === TBT_Rewards_Helper_Rule_Type::DISTRIBUTION) {
            $tags[] = self::REWARDS_SALESRULE_EARNING_CACHE_TAG;

            if ($this->getId()) {
                $tags[] = self::REWARDS_SALESRULE_EARNING_CACHE_TAG . '_' . $this->getId();
            }
        }

        return $tags;
    }

}
