<?php

class TBT_RewardsPointsOnly_Model_Mysql4_Rule_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('rewardspointsonly/rule');
    }

    public function addFilterForDate($date = null)
    {
        if (!$date) {
            $date = Mage::getModel('core/date')->gmtDate();
        }

        $formatedDate = Varien_Date::formatDate($date, false);

        $this->getSelect()->where('from_date is null or from_date <= ?', $formatedDate)
                ->where('to_date is null or to_date >= ?', $formatedDate);

        return $this;
    }

    public function addFilterForWebsite($wId)
    {
        $this->getSelect()->where('FIND_IN_SET(?, website_ids)', $wId);

        return $this;
    }

    public function addFilterForCustomerGroup($gId)
    {
        $this->getSelect()->where('FIND_IN_SET(?, customer_group_ids)', $gId);

        return $this;
    }

    public function addFilterIsActive($isActive = 1)
    {
        $this->addFieldToFilter('is_active', (int)$isActive ? 1 : 0);

        return $this;
    }

    public function sortByPriority($direction = 'ASC')
    {
        $this->getSelect()->order('sort_order', $direction);

        return $this;
    }

    /**
     * Find product attribute in conditions or actions
     *
     * @param string $attributeCode
     * @return Mage_CatalogRule_Model_Resource_Rule_Collection
     */
    public function addAttributeInConditionFilter($attributeCode)
    {
        $match = sprintf('%%%s%%', substr(serialize(array('attribute' => $attributeCode)), 5, -1));
        $this->addFieldToFilter('conditions_serialized', array('like' => $match));

        return $this;
    }
}