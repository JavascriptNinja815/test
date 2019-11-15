<?php

class TBT_RewardsPointsOnly_Model_Mysql4_Rule extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('rewardspointsonly/rule', 'rule_id');
    }

    public function getRulesForValidation($wId, $gId, $date = null)
    {
        $collection = Mage::getModel('rewardspointsonly/rule')->getCollection();
        $collection->addFilterForDate($date);
        $collection->addFilterForWebsite($wId);
        $collection->addFilterForCustomerGroup($gId);
        $collection->addFilterIsActive();
        $collection->sortByPriority();

        $collection->load();

        return $collection->getItems();
    }
}
