<?php

$installer = $this;
$installer->startSetup();

$helper = Mage::helper('rewards');
$ruleCollection = Mage::getModel('rewards/special')->getCollection();

foreach ($ruleCollection as $rule) {
    $conditions = $helper->unhashIt($rule->getConditionsSerialized());
    if (!is_array($conditions)) {
        $conditions = array($conditions);
    }
    
    foreach ($conditions as $key => $value) {
        if ($value === TBT_Rewards_Model_Special_Action::ACTION_RATING) {
            $condition[$key] = TBT_Rewards_Model_Special_Action::ACTION_WRITE_REVIEW;
            $rule->setConditionsSerialized($helper->hashIt($condition));
            $rule->save();
        }
    }
}

$installer->endSetup();
