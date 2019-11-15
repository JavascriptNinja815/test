<?php

/**
 * Class TBT_RewardsApi2_Model_Resource_Transfer_Collection
 *
 * Extended collection class from TBT_Rewards module to act as adaptor
 * for mapping of attribute names until TBT_Rewards_Transfer model and all it's associated
 * classes are refactored in subsequent releases of ST
 *
 * @extends    TBT_Rewards_Helper_Data
 * @category   TBT
 * @package    TBT_RewardsApi2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsApi2_Model_Resource_Transfer_Collection extends TBT_Rewards_Model_Mysql4_Transfer_Collection
{

    /**
     * Sets attribute mappings for the collection and adds extra column aliases
     * @see Varien_Data_Collection_Db::_getMappedField()
     * @param array $fieldMappings.
     *      Array Keys:     original column name
     *      Array Values:   attribute alias
     * @return $this
     */
    public function setAttributeMapping($fieldMappings)
    {
        $mapping = array_flip($fieldMappings);
        $this->_map = array('fields' => $mapping);
        $this->getSelect()->columns($mapping);

        return $this;
    }
}