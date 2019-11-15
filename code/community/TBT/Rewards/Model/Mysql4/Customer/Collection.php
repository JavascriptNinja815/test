<?php

class TBT_Rewards_Model_Mysql4_Customer_Collection extends Mage_Customer_Model_Entity_Customer_Collection
{
    protected $_logJoined = false;

    protected function _construct()
    {
        $this->_init('rewards/customer');
    }

    /**
     * Joins this collection with the log/customer collection
     * and produces a last_login column for the last store the customer visited
     *
     * @param int|array storeIds (optional) to specify which stores to grab the last login for
     * @return TBT_Rewards_Model_Mysql4_Customer_Collection
     */
    public function addLastLoginToSelect($storeIds = null)
    {
       if ($this->_logJoined) {
           return $this;
       }

       $logTable = $this->getTable('log/customer');

       $this->getSelect()->join(
                   array('log' => $logTable),
                   'e.entity_id = log.customer_id',
                   array(
                         "last_login" => "MAX(log.login_at)",
                        )
               );

       if (!empty($storeIds)){
           if (!is_array($storeIds)){
               $storeIds = array($storeIds);
           }

           $this->getSelect()->where("`log`.`store_id` IN('".implode("','", $storeIds)."')");
       }

       $this->getSelect()->group('log.customer_id');

       $this->_logJoined = true;

       return $this;
    }

    /**
     * Provides a non-buggy way to count elements in this collection using MySQL if there is a join on this table.
     * @see Varien_Data_Collection::getSelectCountSql()
     * @return Varien_Db_Select|string
     */
    public function getSelectCountSql()
    {
    	if ($this->_logJoined){    		
    		return "SELECT COUNT(*) FROM ({$this->getSelectSql(true)}) AS collection";
    	}    	
    	return parent::getSelectCountSql();
    }
    
    /**
     * Prepare Signups Metrics Collection
     * @param string $range
     * @return \TBT_Rewards_Model_Mysql4_Customer_Collection
     */
    public function prepareMetricsSignups($range)
    {
        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();

        $select = $this->getSelect();
        $select->join(new Zend_Db_Expr('(SELECT @aux := 0)'), '');

        $select->reset(Zend_Db_Select::COLUMNS);

        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($range, 0, 0);

        $select->columns(array(
            'quantity' => new Zend_Db_Expr(
                '(@aux := @aux + COUNT(entity_id))'
            ),
            'range' => new Zend_Db_Expr(
                "MAX(".$this->_getRangeExpression($range, "DATE_ADD(created_at, INTERVAL $gmtOffset SECOND)").")"
            )
        ));
        
        $select->group($this->_getRangeExpression($range, "DATE_ADD(created_at, INTERVAL $gmtOffset SECOND)"));

        $select->where("DATE_FORMAT(DATE_ADD(created_at, INTERVAL $gmtOffset SECOND), '%Y-%m-%d') >= '" . $dateRange['from']->toString('Y-MM-dd') . "'");
        return $this;
    }
    
    /**
     * Getter for Total New Signups Before Range
     * @param string $range
     * @return int|float
     */
    public function getTotalSignupsBeforePeriod($range)
    {
        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();

        $select = clone $this->getSelect();

        $select->reset(Zend_Db_Select::COLUMNS);

        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($range, 0, 0);

        $select->columns(array(
            'quantity' => new Zend_Db_Expr(
                'COUNT(entity_id)'
            )
        ));
        
        $select->where("DATE_FORMAT(DATE_ADD(created_at, INTERVAL $gmtOffset SECOND), '%Y-%m-%d') < '" . $dateRange['from']->toString('Y-MM-dd') . "'");
        
        $connection = $this->getConnection();
        $result = $connection->fetchRow($select);
        
        return (float) $result['quantity'];
    }
    
    /**
     * Getter for Total New Signups By Date
     * @param string $range
     * @return int|float
     */
    public function getTotalSignupsByDate($startDate = null, $after = true)
    {
        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();

        $select = clone $this->getSelect();

        $select->reset(Zend_Db_Select::COLUMNS);

        $select->columns(array(
            'quantity' => new Zend_Db_Expr(
                'COUNT(entity_id)'
            )
        ));
        
        if ($startDate) {
            $operand = ($after) ? '>=' : '<';
            $select->where("DATE_FORMAT(DATE_ADD(created_at, INTERVAL $gmtOffset SECOND), '%Y-%m-%d') ". $operand ." '" . $startDate . "'");
        }
        
        $connection = $this->getConnection();
        $result = $connection->fetchRow($select);
        
        return (float) $result['quantity'];
    }
    
    /**
     * Get range expression
     *
     * @param string $range
     * @return Zend_Db_Expr
     */
    protected function _getRangeExpression($range, $attribute = '{{attribute}}')
    {
        $expression = $this->getConnection()->getDateFormatSql($attribute, '%Y-%m-%d');

        return $expression;
    }
    
    /**
     * Retrieve query for attribute with timezone conversion
     *
     * @param string $range
     * @param string $attribute
     * @param mixed $from
     * @param mixed $to
     * @return string
     */
    protected function _getTZRangeOffsetExpression($range, $attribute, $from = null, $to = null)
    {
        return str_replace(
            '{{attribute}}',
            Mage::getResourceModel('sales/report_order')
                    ->getStoreTZOffsetQuery('', $attribute, $from, $to),
            $this->_getRangeExpression($range)
        );
    }

    /**
     * Provides a non-buggy way to get all Ids of this collection using MySQL if there is a join on this table.
     * @see Varien_Data_Collection::_getAllIdsSelect()
     * @return Varien_Db_Select
     */
    protected function _getAllIdsSelect($limit = null, $offset = null)
    {
    	$idsSelect = $this->getConnection()->select();
    	$idsSelect->from($this->getSelect());
    	$idsSelect->reset(Zend_Db_Select::COLUMNS);
    	$idsSelect->columns('t.' . $this->getEntity()->getIdFieldName());
    	$idsSelect->limit($limit, $offset);
    
    	return $idsSelect;
    }
}
