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
 * Mysql Catalog Rule Rule
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Mysql4_CatalogRule_Rule extends Mage_CatalogRule_Model_Mysql4_Rule
{
    protected $_loadedRules = array();
    
    protected $_addPriceData = true;
    
    /**
     * @param   int|string $date
     * @param   int $wId
     * @param   int $gId
     * @return  Zend_Db_Select
     */
    public function getActiveCatalogruleProductsReader($date, $wId, $gId)
    {
        //$read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $read = $this->_getReadAdapter();
        //$catalogrule_price_table = Mage::getConfig()->getTablePrefix() . ;
        $catalogrule_price_table = $this->getTable('rewards/catalogrule_product');

        $select = $read->select()
            ->from(array('p' => $catalogrule_price_table),
                array('product_id', 'rules_hash'))
            ->where('p.rule_date = ?', $date)
            ->where('p.customer_group_id = ?', $gId)
            ->where('p.website_id = ?', $wId)
            ->where('p.rules_hash IS NOT NULL');
        $this->_filterActiveCatalogruleProducts($select, $wId);

        return $select;
    }

    /**
     * @param   int|string $date
     * @param   int $wId
     * @param   int $gId
     * @return  array | false    applicable redemption product_id and rules_hash.
     */
    public function getActiveCatalogruleProducts($date, $wId, $gId)
    {
        $read = $this->_getReadAdapter();
        $select = $this->getActiveCatalogruleProductsReader($date, $wId, $gId);
        return $read->fetchAll($select);
    }

    /**
     * @param Zend_Db_Select $select
     * @return Zend_Db_Select
     */
    protected function _filterActiveCatalogruleProducts(&$select, $websiteId)
    {
        $storeId = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId();
        Mage::getModel('rewards/catalog_product_visibility')->addVisibileFilterToCR($select, $storeId);
        Mage::getModel('rewards/catalog_product_status')->addVisibileFilterToCR($select, $storeId);

        return $this;
    }

    /**
     * @param   int|string $date
     * @param   int $wId
     * @param   int $gId if gId is set as null then filtering on group is skipped
     * @param   int $pId
     * @return  array | false    applicable redemption rules hash.
     */
    public function getApplicableRedemptionRewards($date, $wId, $gId, $pId)
    {
        $date = $this->formatDate($date, false);
        $read = $this->_getReadAdapter();

        $select = $read->select()
            ->from($this->getTable('rewards/catalogrule_product'), 'rules_hash')
            ->where('rule_date = ?', $date)
            ->where('website_id = ?', $wId)
            ->where('product_id = ?', $pId);
        if ($gId !== null) {
            $select->where('customer_group_id = ?', $gId);
        }

        $rulesHash = $read->fetchOne($select);
        if ($rulesHash) {
            $rules = Mage::helper('rewards')->unhashIt($rulesHash);
        } else {
            $rules = array();
        }
        if (!isset($rules['0'])) {
            $rules = array();
        }

        return $rules;
    }

    /**
     * Returns the applicable reward array from the catalog product price table.
     *
     * @param date $date
     * @param int $wId
     * @param int $gId
     * @param int $pId
     * @param int $ruleId
     * @return array | false
     */
    public function getApplicableReward($date, $wId, $gId, $pId, $ruleId)
    {
        $applicableRules = $this->getApplicableRedemptionRewards($date, $wId, $gId, $pId);

        foreach ($applicableRules as &$applicableRule) {
            $applicableRule = (array) $applicableRule;
            if ($applicableRule['rule_id'] == $ruleId) {
                return $applicableRule;
            }
        }
        return false;
    }

    /**
     * Generate product redemption rule hashes for specified date range
     * If from date is not defined - will be used previous day by UTC
     * If to date is not defined - will be used next day by UTC
     * Mimics Mage_CatalogRule_Model_Resource_Rule::applyAllRulesForDateRange() but for points redemption rules.
     * 
     * TODO: split this up into multiple methods
     *
     * @param int $productId
     * @param int|string|null $fromDate
     * @param int|string|null $toDate
     * @return self
     */
    public function applyAllRedemptionRulesForDateRange($productId = null, $fromDate = null, $toDate = null)
    {
        $write = $this->_getWriteAdapter();
        $write->beginTransaction();

        //Mage::dispatchEvent('catalogrule_before_apply', array('resource' => $this));

        if ($productId) {
            $this->updatePricesAfterProductSave($productId);
        }

        $clearOldData = false;
        if ($fromDate === null) {
            $fromDate = mktime(0, 0, 0, date('m'), date('d') - 1);
            // If fromDate not specified we can delete all data oldest than 1 day
            $clearOldData = true;
        }
        if (is_string($fromDate)) {
            $fromDate = strtotime($fromDate);
        }
        if ($toDate === null) {
            $toDate = mktime(0, 0, 0, date('m'), date('d') + 1);
        }
        if (is_string($toDate)) {
            $toDate = strtotime($toDate);
        }

        $product = null;
        if ($productId instanceof Mage_Catalog_Model_Product) {
            $product = $productId;
            $productId = $productId->getId();
        }

        $this->removeRedemptionRulesForDateRange($fromDate, $toDate, $productId);
        if ($clearOldData) {
            $this->deleteOldRedemptionData($fromDate, $productId);
        }

        $dayPrices = array();
        try {
            $this->_addPriceData = false;
            
            // Update products rules hashes per each website separately because of max join limit in mysql
            foreach (Mage::app()->getWebsites(false) as $website) {
                $productsStmt = $this->_getRuleProductsStmt(
                    $fromDate,
                    $toDate,
                    $productId,
                    $website->getId()
                );

                $dayPrices  = array();
                $stopFlags  = array();
                $prevKey    = null;

                while ($ruleData = $productsStmt->fetch()) {
                    $rule = $this->_getCatalogRule($ruleData['rule_id']);
                    if (!$rule) {
                        continue;
                    }

                    if (!$rule->isRedemptionRule()) {
                        continue;
                    }

                    $effect = $rule->getEffect();
                    if (empty($effect)) {
                        continue;
                    }
                    
                    if (!$this->_addPriceData 
                            && $ruleData['product_type'] == 'bundle' 
                            && $rule->getPointsOnlyMode()) {
                        continue;
                    }

                    $ruleProductId = $ruleData['product_id'];
                    $productKey = $ruleProductId . '_'
                        . $ruleData['website_id'] . '_'
                        . $ruleData['customer_group_id'];

                    if ($prevKey && ($prevKey != $productKey)) {
                        $stopFlags = array();
                    }

                    // Build hashes for each day
                    for ($time = $fromDate; $time <= $toDate; $time += self::SECONDS_IN_DAY) {
                        if (($ruleData['from_time'] == 0 || $time >= $ruleData['from_time'])
                            && ($ruleData['to_time'] == 0 || $time <=$ruleData['to_time'])
                        ) {
                            $priceKey = $time . '_' . $productKey;

                            if (isset($stopFlags[$priceKey])) {
                                continue;
                            }

                            if (!isset($dayPrices[$priceKey])) {
                                $dayPrices[$priceKey] = array(
                                    'rule_date'         => $time,
                                    'website_id'        => $ruleData['website_id'],
                                    'customer_group_id' => $ruleData['customer_group_id'],
                                    'product_id'        => $ruleProductId,
                                    'rules_hash'        => $this->_generateRuleProductRedemptionHash($rule),
                                    'latest_start_date' => $ruleData['from_time'],
                                    'earliest_end_date' => $ruleData['to_time']
                                );
                            } else {
                                $dayPrices[$priceKey]['rules_hash'] = $this->_generateRuleProductRedemptionHash(
                                    $rule,
                                    $dayPrices[$priceKey]
                                );
                                $dayPrices[$priceKey]['latest_start_date'] = max(
                                    $dayPrices[$priceKey]['latest_start_date'],
                                    $ruleData['from_time']
                                );
                                $dayPrices[$priceKey]['earliest_end_date'] = min(
                                    $dayPrices[$priceKey]['earliest_end_date'],
                                    $ruleData['to_time']
                                );
                            }

                            if ($ruleData['action_stop']) {
                                $stopFlags[$priceKey] = true;
                            }
                        }
                    }

                    $prevKey = $productKey;
                    if (count($dayPrices) > 1000) {
                        $this->_saveRuleProductRedemptionHash($dayPrices);
                        $dayPrices = array();
                    }
                }
                $this->_saveRuleProductRedemptionHash($dayPrices);
            }
            
            $this->_addPriceData = true;

            $this->_saveRuleProductRedemptionHash($dayPrices);

            $write->commit();
        } catch (Exception $e) {
            $write->rollback();
            throw $e;
        }

        // TODO: dispatch an appropriate event here
//         Mage::dispatchEvent('catalogrule_after_apply', array(
//             'product' => $product,
//             'product_condition' => $productCondition
//         ));

        return $this;
    }

    /**
     * Updates reward rules
     * @param int|Mage_Catalog_Model_Product $product
     * @param boolean $forceWithFlat
     * @return \TBT_Rewards_Model_Mysql4_CatalogRule_Rule
     * @throws Exception
     */
    public function updatePricesAfterProductSave($product, $forceWithFlat = false)
    {
        $productId = $product;
        if ($product instanceof Mage_Catalog_Model_Product) {
            $productId = $product->getId();
        }

        $catalogFlatHelper = Mage::helper('catalog/product_flat');
        $eavConfig = Mage::getSingleton('eav/config');
        $priceAttribute = $eavConfig->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'price');

        $write = $this->_getWriteAdapter();
        $write->beginTransaction();

        $query = "";

        try {
            foreach (Mage::app()->getWebsites(false) as $website) {
                $storeId = $website->getDefaultStore()->getId();
                $query = "UPDATE " . $this->getTable('catalogrule/rule_product_price') . " as crpp ";

                if ($forceWithFlat && $catalogFlatHelper->isEnabled() && $storeId && $catalogFlatHelper->isBuilt($storeId)) {
                    $query .= "INNER JOIN " . $this->getTable('catalog/product_flat') . '_' . $storeId . " as cp ";
                    $query .= "on cp.entity_id = crpp.product_id ";

                    $query .= "SET crpp.rule_price = cp.price ";
                } else {
                    $query .= "INNER JOIN " . $this->getTable(array('catalog/product', $priceAttribute->getBackendType())) . " as cpd ";
                    $query .= "on cpd.entity_id = crpp.product_id AND cpd.store_id = 0 AND cpd.attribute_id = " . $priceAttribute->getId() . " ";

                    $query .= "LEFT JOIN " . $this->getTable(array('catalog/product', $priceAttribute->getBackendType())) . " as cp ";
                    $query .= "on cp.entity_id = crpp.product_id AND cp.store_id = ". $storeId . " AND cp.attribute_id = " . $priceAttribute->getId() . " ";

                    $query .= "SET crpp.rule_price = COALESCE(cp.value, cpd.value, 0) ";
                }

                $query .= "WHERE crpp.product_id = " . $productId . " AND crpp.website_id = " . $website->getId();

                $write->query($query);
            }

            $write->commit();
        } catch (Exception $exc) {
            $write->rollBack();
            throw $exc;
        }

        return $this;
    }

    /**
     * Remove product redemption rule hashes for specified date range and product.
     * Mimics Mage_CatalogRule_Model_Resource_Rule::removeCatalogPricesForDateRange() but for points redemption rules.
     *
     * @param int|string $fromDate
     * @param int|string $toDate
     * @param int|null $productId
     * @return self
     */
    public function removeRedemptionRulesForDateRange($fromDate, $toDate, $productId = null)
    {
        $write = $this->_getWriteAdapter();
        $conds = array();
        $cond = $write->quoteInto('rule_date between ?', $this->formatDate($fromDate));
        $cond = $write->quoteInto($cond . ' and ?', $this->formatDate($toDate));
        $conds[] = $cond;
        if (!is_null($productId)) {
            $conds[] = $write->quoteInto('product_id = ?', $productId);
        }

        $write->delete($this->getTable('rewards/catalogrule_product'), $conds);
        return $this;
    }

    /**
     * Delete old product redemption rule hash data
     * Mimics Mage_CatalogRule_Model_Resource_Rule::deleteOldData() but for points redemption rules.
     *
     * @param unknown_type $date
     * @param mixed $productId
     * @return self
     */
    public function deleteOldRedemptionData($date, $productId = null)
    {
        $write = $this->_getWriteAdapter();
        $conds = array();
        $conds[] = $write->quoteInto('rule_date < ?', $this->formatDate($date));
        if (!is_null($productId)) {
            $conds[] = $write->quoteInto('product_id = ?', $productId);
        }
        $write->delete($this->getTable('rewards/catalogrule_product'), $conds);
        return $this;
    }

    /**
     * Save redemption rule hashes for products to DB
     * Mimics Mage_CatalogRule_Model_Resource_Rule::_saveRuleProductPrices() but for points redemption rules.
     *
     * @param array $arrData
     * @return self
     */
    protected function _saveRuleProductRedemptionHash($arrData)
    {
        if (empty($arrData)) {
            return $this;
        }

        foreach ($arrData as $key => $data) {
            $arrData[$key]['rule_date']          = $this->formatDate($data['rule_date'], false);
            $arrData[$key]['latest_start_date']  = $this->formatDate($data['latest_start_date'], false);
            $arrData[$key]['earliest_end_date']  = $this->formatDate($data['earliest_end_date'], false);
        }

        $this->_getWriteAdapter()->insertOnDuplicate($this->getTable('rewards/catalogrule_product'), $arrData);
        return $this;
    }

    /**
     * Generates a redemption rule hash based on a rule object and a pre-existing rule hash (if one is given).
     * Mimics Mage_CatalogRule_Model_Resource_Rule::_calcRuleProductPrice() but for points redemption rules.
     *
     * @param array $ruleData
     * @param null|array $productData
     * @return string
     */
    protected function _generateRuleProductRedemptionHash($rule, $productData = null)
    {
        $rulesHash = array();
        if ($productData !== null && isset($productData['rules_hash'])) {
            $rulesHash = json_decode(base64_decode($productData['rules_hash']));
        }

        $rulesHash[] = $rule->getHashEntry();

        return base64_encode(json_encode($rulesHash));
    }

    /**
     * Returns a rule and makes sure rules are only ever loaded once
     *
     * @param integer $ruleId
     * @return self
     */
    protected function _getCatalogRule($ruleId)
    {
        if (isset($this->_loadedRules[$ruleId])) {
            return $this->_loadedRules[$ruleId];
        }

        $rule = Mage::getModel('rewards/catalogrule_rule')->load($ruleId);
        $this->_loadedRules[$ruleId] = $rule;

        return $rule;
    }
    
    /**
     * Get DB resource statement for processing query result
     *
     * @param int $fromDate
     * @param int $toDate
     * @param int|null $productId
     * @param int|null $websiteId
     *
     * @return Zend_Db_Statement_Interface

     * @see Mage_CatalogRule_Model_Resource_Rule::_getRuleProductsStmt()
     */
    protected function _getRuleProductsStmt($fromDate, $toDate, $productId = null, $websiteId = null)
    {
        $read = $this->_getReadAdapter();
        /**
         * Sort order is important
         * It used for check stop price rule condition.
         * website_id   customer_group_id   product_id  sort_order
         *  1           1                   1           0
         *  1           1                   1           1
         *  1           1                   1           2
         * if row with sort order 1 will have stop flag we should exclude
         * all next rows for same product id from price calculation
         */
        $select = $read->select()
            ->from(array('rp' => $this->getTable('catalogrule/rule_product')))
            ->where($read->quoteInto('rp.from_time = 0 or rp.from_time <= ?', $toDate)
            . ' OR ' . $read->quoteInto('rp.to_time = 0 or rp.to_time >= ?', $fromDate))
            ->order(array('rp.website_id', 'rp.customer_group_id', 'rp.product_id', 'rp.sort_order', 'rp.rule_id'));

        if (!is_null($productId)) {
            $select->where('rp.product_id=?', $productId);
        }

        /**
         * Join default price and websites prices to result
         */
        if ($this->_addPriceData) {
            $priceAttr  = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'price');
            $priceTable = $priceAttr->getBackend()->getTable();
            $attributeId= $priceAttr->getId();

            $joinCondition = '%1$s.entity_id=rp.product_id AND (%1$s.attribute_id=' . $attributeId
                . ') and %1$s.store_id=%2$s';

            $select->join(
                array('pp_default'=>$priceTable),
                sprintf($joinCondition, 'pp_default', Mage_Core_Model_App::ADMIN_STORE_ID),
                array('default_price'=>'pp_default.value')
            );
        } else {
            $this->addProductTypeToStatement($select);
        }

        if ($websiteId !== null) {
            $website  = Mage::app()->getWebsite($websiteId);
            $defaultGroup = $website->getDefaultGroup();
            if ($defaultGroup instanceof Mage_Core_Model_Store_Group) {
                $storeId = $defaultGroup->getDefaultStoreId();
            } else {
                $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
            }

            $select->joinInner(
                array('product_website' => $this->getTable('catalog/product_website')),
                'product_website.product_id=rp.product_id ' .
                'AND rp.website_id=product_website.website_id ' .
                'AND product_website.website_id='.$websiteId,
                array()
            );

            if ($this->_addPriceData) {
                $tableAlias = 'pp'.$websiteId;
                $fieldAlias = 'website_'.$websiteId.'_price';
                $select->joinLeft(
                    array($tableAlias=>$priceTable),
                    sprintf($joinCondition, $tableAlias, $storeId),
                    array($fieldAlias=>$tableAlias.'.value')
                );
            }
        } else {
            if ($this->_addPriceData) {
                foreach (Mage::app()->getWebsites() as $website) {
                    $websiteId  = $website->getId();
                    $defaultGroup = $website->getDefaultGroup();
                    if ($defaultGroup instanceof Mage_Core_Model_Store_Group) {
                        $storeId = $defaultGroup->getDefaultStoreId();
                    } else {
                        $storeId = Mage_Core_Model_App::ADMIN_STORE_ID;
                    }

                    $tableAlias = 'pp' . $websiteId;
                    $fieldAlias = 'website_' . $websiteId . '_price';
                    $select->joinLeft(
                        array($tableAlias => $priceTable),
                        sprintf($joinCondition, $tableAlias, $storeId),
                        array($fieldAlias => $tableAlias.'.value')
                    );
                }
            }
        }
        
        return $read->query($select);
    }
    
    /**
     * Add product type to select statement
     * 
     * @param Varien_Db_Select $select
     * @return TBT_Rewards_Model_Mysql4_CatalogRule_Rule
     */
    protected function addProductTypeToStatement($select)
    {
        $productTable = $this->getTable('catalog/product');
        $select->joinInner(
            array('pe' => $productTable),
            'pe.entity_id = rp.product_id',
            array('product_type' => 'type_id')
        );
        
        return $this;
    }
}
