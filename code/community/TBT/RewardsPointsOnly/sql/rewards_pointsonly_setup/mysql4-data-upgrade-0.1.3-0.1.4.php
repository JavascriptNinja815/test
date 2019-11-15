<?php

$installer = $this;

$installer->startSetup();

if (
    $installer->getConnection()->tableColumnExists($installer->getTable('catalogrule/rule'), 'points_only_mode')
    && $installer->getConnection()->tableColumnExists($installer->getTable('catalogrule/rule'), 'points_action')
) {
    $pointsOnlyCatalogRulesCollection = Mage::getModel('catalogrule/rule')->getCollection()
        ->addFieldToFilter('points_only_mode', 1)
        ->addFieldToFilter('points_action', array('notnull' => true))
        ->load();

    $rewardsRuleLabelTableExists = false;

    if ($installer->getConnection()->isTableExists('rewards_catalogrule_label')) {
        $rewardsRuleLabelTableExists = true;
    }

    foreach ($pointsOnlyCatalogRulesCollection->getItems() as $rule) {
        $sendAdminAlert = false;
        $oldRuleData = Mage::helper('rewards')->hashIt($rule->getData());

        Mage::log("======= Processing Points Only Catalog Rule Id #" . $rule->getId() . " =======", null, 'rewards_migration_pointsonly.log');
        Mage::log("-> Old Rule Data: " . $oldRuleData, null, 'rewards_migration_pointsonly.log');
        
        if ($rewardsRuleLabelTableExists) {
            $ruleLabelsQuery = "select * from rewards_catalogrule_label where rule_id = " . $rule->getId();
            $ruleLabelsData = $installer->getConnection()->fetchAll($ruleLabelsQuery);

            if (!empty($ruleLabelsData)) {
                $ruleLabelsDataHash = Mage::helper('rewards')->hashIt($ruleLabelsData);
                Mage::log("-> Old Rule Labels Data: " . $ruleLabelsDataHash, null, 'rewards_migration_pointsonly.log');
            }
        }

        switch ($rule->getPointsCatalogruleSimpleAction()) {
            case 'by_fixed' :
                try {
                    $ruleData = $rule->getData();
                    unset($ruleData['rule_id']);

                    $newRule = Mage::getModel('rewardspointsonly/rule');
                    $newRule->setData($ruleData);
                    $newRule->setCustomerGroupIds($rule->getCustomerGroupIds());
                    $newRule->setWebsiteIds($rule->getWebsiteIds());
                    $newRule->setId(null);

                    $conditionsAggregator = $newRule->getConditions()->getAggregator();
                    $conditionsTrue = (bool) $newRule->getConditions()->getValue();

                    $priceCondition = Mage::getModel('catalogrule/rule_condition_product')
                        ->setType('catalogrule/rule_condition_product')
                        ->setAttribute('price')
                        ->setOperator('<=')
                        ->setValue($rule->getPointsCatalogruleDiscountAmount());

                    if (!$conditionsTrue) {
                        $priceCondition->setOperator('>');
                    }

                    $newRule->getConditions()->addCondition($priceCondition);

                    if ($newRule->getConditions()->getAggregator() !== "all") {
                        $newRule->setIsActive(0);

                        Mage::log("-> [warn] Rule Conditions are not aggregated with `ALL` therefore price restriction doesn't strictly apply! Rule will be created as Inactive!", null, 'rewards_migration_pointsonly.log');

                        $sendAdminAlert = true;
                    }

                    $newRule->save();
                    Mage::log("-> Migrated Successfully! Price Conditions was added!", null, 'rewards_migration_pointsonly.log');

                    if ($sendAdminAlert) {
                        $adminNotification = Mage::getModel('adminnotification/inbox');
                        $adminNotification->setSeverity(1)
                            ->setTitle('Rewards Points Only Old Rule #id ' . $rule->getId() . ' was migrated with warnings therefore it was created as Inactive. This rule had to have a strict price restriction based on new rule structure, but existing conditions are created with `ANY` aggregator. Please correct the conditions or re-check the rule definitions and activate the rule whenever you consider that is ready.')
                            ->setDescription('Rewards Points Only Old Rule #id ' . $rule->getId() . ' was migrated with warnings therefore it was created as Inactive. This rule had to have a strict price restriction, but existing conditions are created with `ANY` aggregator. Please correct the conditions or re-check the rule definitions and activate the rule whenever ready.')
                            ->save();
                    }
                } catch (Exception $exc) {
                    Mage::logException($exc);
                    Mage::log("-> [error] New Rule Cannot Be Created! Error Message: " . $exc->getMessage(), null, 'rewards_migration_pointsonly.log');
                }

                break;
            case 'by_percent' :
                if ((float) $rule->getPointsCatalogruleDiscountAmount() === 100.00) {
                    try {
                        $ruleData = $rule->getData();
                        unset($ruleData['rule_id']);

                        $newRule = Mage::getModel('rewardspointsonly/rule');
                        $newRule->setData($ruleData);
                        $newRule->setCustomerGroupIds($rule->getCustomerGroupIds());
                        $newRule->setWebsiteIds($rule->getWebsiteIds());
                        $newRule->setId(null);
                        $newRule->save();
                        Mage::log("-> Migrated Successfully!", null, 'rewards_migration_pointsonly.log');
                    } catch (Exception $exc) {
                        Mage::logException($exc);
                        Mage::log("-> [error] New Rule Cannot Be Created! Error Message: " . $exc->getMessage(), null, 'rewards_migration_pointsonly.log');
                    }
                }

                break;
            case 'to_fixed' :
                if ((float) $rule->getPointsCatalogruleDiscountAmount() === 0.00) {
                    try {
                        $ruleData = $rule->getData();
                        unset($ruleData['rule_id']);
                        
                        $newRule = Mage::getModel('rewardspointsonly/rule');
                        $newRule->setData($ruleData);
                        $newRule->setCustomerGroupIds($rule->getCustomerGroupIds());
                        $newRule->setWebsiteIds($rule->getWebsiteIds());
                        $newRule->setId(null);
                        $newRule->save();
                        Mage::log("-> Migrated Successfully!", null, 'rewards_migration_pointsonly.log');
                    } catch (Exception $exc) {
                        Mage::logException($exc);
                        Mage::log("-> [error] New Rule Cannot Be Created! Error Message: " . $exc->getMessage(), null, 'rewards_migration_pointsonly.log');
                    }
                }

                break;
            case 'to_percent' :
                if ((float) $rule->getPointsCatalogruleDiscountAmount() === 0.00) {
                    try {
                        $ruleData = $rule->getData();
                        unset($ruleData['rule_id']);

                        $newRule = Mage::getModel('rewardspointsonly/rule');
                        $newRule->setData($ruleData);
                        $newRule->setCustomerGroupIds($rule->getCustomerGroupIds());
                        $newRule->setWebsiteIds($rule->getWebsiteIds());
                        $newRule->setId(null);
                        $newRule->save();
                        Mage::log("-> Migrated Successfully!", null, 'rewards_migration_pointsonly.log');
                    } catch (Exception $exc) {
                        Mage::logException($exc);
                        Mage::log("-> [error] New Rule Cannot Be Created! Error Message: " . $exc->getMessage(), null, 'rewards_migration_pointsonly.log');
                    }
                }

                break;
        }

        $rule->delete();
    }

    if ($pointsOnlyCatalogRulesCollection->count() > 0) {
        try {
            Mage::getModel('catalogrule/rule')->applyAll();
            Mage::log("-> Catalog Rules applied/reindexed successfully after Points Only Migration!", null, 'rewards_migration_pointsonly.log');
        } catch (Exception $exc) {
            Mage::logException($exc);
            Mage::log("-> [error] Rules cannot be applied/reindexed! Error Message: " . $exc->getMessage(), null, 'rewards_migration_pointsonly.log');
        }
    }
}

$installer->endSetup();
