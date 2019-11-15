<?php

/**
 * Class TBT_Reports_Helper_Collection
 * Used to share functionality among all collection classes of this module
 */

class TBT_Reports_Helper_Collection extends Mage_Core_Helper_Abstract
{
    /**
     * Given a collection, will return
     * a distinct list of items in the column specified
     * Does not modify original collection
     *
     * @param Varien_Data_Collection_Db|Varien_Db_Select
     * @param string column name to extract
     * @param bool $returnSelectObject, if true, will return Varien_Db_Select object, otherwise an array
     * @return array|Varien_Db_Select
     */
    public function extractColumn($collection, $column = "id", $returnSelectObject = true)
    {
        if (!$collection) throw new Exception("Collection cannot be null");
        if ($collection instanceof Varien_Db_Select) {
            $select = clone $collection;
        } else {
            $select = clone ($collection->getSelect());
        }

        $select->reset(Zend_Db_Select::COLUMNS)
            ->distinct()
            ->columns($column);

        if (!$returnSelectObject && $collection instanceof Varien_Data_Collection_Db) {
            return $this->loadColumn($collection->getConnection(), $select, $column);
        }

        return $select;
    }

    /**
     * Given a connection, select statement and a specific column,
     * will load the statement, and return an array with the results
     *
     * @param Varien_Db_Adapter_Interface $connection
     * @param Varien_Db_Select $select
     * @param string $column
     * @return array
     */
    public function loadColumn($connection, $select, $column = "id")
    {
        $data = array();
        $items = $connection->fetchAll($select);
        foreach ($items as $item) {
            array_push($data, $item[$column]);
        }

        return $data;
    }
}