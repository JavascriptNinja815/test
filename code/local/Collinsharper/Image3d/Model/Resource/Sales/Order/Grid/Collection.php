<?php

class Collinsharper_Image3d_Model_Resource_Sales_Order_Grid_Collection extends Mage_Sales_Model_Resource_Order_Grid_Collection
{

    /**
     * The base select is grouped; this function is overridden to simply count the number of
     * rows using the base select as a subquery.
     *
     * @return Zend_Db_Select
     */
    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(Zend_Db_Select::GROUP);
        $countSelect->from('', 'COUNT(DISTINCT main_table.entity_id)');

        return $countSelect;
    }

}
