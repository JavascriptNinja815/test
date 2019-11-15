<?php

class TBT_Rewards_Model_Mysql4_Transfer_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
	
	protected $didSelectCustomerName = false;
	protected $didSelectCurrency = false;
	
	public function _construct() {
		$this->_init ( 'rewards/transfer' );
	}
    
    /**
     * 
     * 
     * (overrides parent method)
     */
    public function _initSelect () {
        parent::_initSelect();
        return $this;
    }

    /**
     * Get SQL for get record count
     *
     * @return Varien_Db_Select
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $countSelect = clone $this->getSelect();

        if (!$this->didSelectCustomerName) {
            $mainTable = $this->getMainTable();

            // reset any joins that are on this query
            $countSelect->reset(Zend_Db_Select::FROM);
            $countSelect->from(array('main_table' => $mainTable));

            $defaultCurrencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
            $countSelect->joinLeft(
                array('currency_table' => $this->getTable('currency')),
                "currency_table.rewards_currency_id=$defaultCurrencyId",
                array('currency' => 'caption', 'currency_id' => 'rewards_currency_id')
            );
        }

        $countSelect->reset(Zend_Db_Select::GROUP);

        $countSelect->reset(Zend_Db_Select::ORDER);
        $countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $countSelect->reset(Zend_Db_Select::COLUMNS);

        $countSelect->columns('COUNT(*)');

        return $countSelect;
    }
	
	/**
	 * Adds customer info to select
	 * @return  TBT_Rewards_Model_Mysql4_Transfer_Collection
	 */
	public function selectCurrency() 
        {
            if (!$this->didSelectCurrency) {
                $defaultCurrencyId = Mage::helper('rewards/currency')->getDefaultCurrencyId();
                $this->getSelect()->joinLeft(
                    array('currency_table' => $this->getTable('currency')), 
                    "currency_table.rewards_currency_id=$defaultCurrencyId", 
                    array('currency' => 'caption', 'currency_id' => 'rewards_currency_id')
                );
                
                $this->didSelectCurrency = true;
            }
            
            return $this;
	}
	
	/**
	 * Add Filter by store
	 * @deprecated not supported in current stable version
	 *
	 * @param int|Mage_Core_Model_Store $store
	 * @return TBT_Rewards_Model_Mysql4_Transfer_Collection
	 */
	public function addStoreFilter($store) {
		return $this;
	}
	
	/**
	 * Adds customer info to select
	 *
	 * @return  TBT_Rewards_Model_Mysql4_Transfer_Collection
	 */
	public function selectCustomerName() {
		if (! $this->didSelectCustomerName) {
			/* @var $customer TBT_Rewards_Model_Customer */
			$customer = Mage::getModel ( 'rewards/customer' );
			$firstname = $customer->getAttribute ( 'firstname' );
			$lastname = $customer->getAttribute ( 'lastname' );

			$this->getSelect ()->joinLeft ( array ('customer_lastname_table' => $lastname->getBackend ()->getTable () ), 'customer_lastname_table.entity_id=main_table.customer_id
                 AND customer_lastname_table.attribute_id = ' . ( int ) $lastname->getAttributeId () . '
                 ', array ('customer_lastname' => 'value' ) )->joinLeft ( array ('customer_firstname_table' => $firstname->getBackend ()->getTable () ), 'customer_firstname_table.entity_id=main_table.customer_id
                 AND customer_firstname_table.attribute_id = ' . ( int ) $firstname->getAttributeId () . '
                 ', array ('customer_firstname' => 'value' ) );
			$this->didSelectCustomerName = true;
		}
                
		return $this;
	}
	
	/**
	 * Adds the full customer name to the query.
	 *
	 * @param string|$alias What to name the column
	 * @return TBT_Rewards_Model_Mysql4_Transfer_Collection
	 */
	public function selectFullCustomerName($alias = 'fullname') {
		$this->selectCustomerName ();
		
		$fields = array ();
		$fields ['firstname'] = 'firstname';
		$fields ['lastname'] = 'firstname';
		
		$expr = 'CONCAT(' . (isset ( $fields ['prefix'] ) ? 'IF({{prefix}} IS NOT NULL AND {{prefix}} != "", CONCAT({{prefix}}," "), ""),' : '') . '{{firstname}}' . (isset ( $fields ['middlename'] ) ? ',IF({{middlename}} IS NOT NULL AND {{middlename}} != "", CONCAT(" ",{{middlename}}), "")' : '') . '," ",{{lastname}}' . (isset ( $fields ['suffix'] ) ? ',IF({{suffix}} IS NOT NULL AND {{suffix}} != "", CONCAT(" ",{{suffix}}), "")' : '') . ')';
		
		$expr = str_replace ( "{{firstname}}", "customer_firstname_table.value", $expr );
		$expr = str_replace ( "{{lastname}}", "customer_lastname_table.value", $expr );
		
		$fullExpression = $expr;
		
		$this->getSelect ()->from ( null, array ($alias => $fullExpression ) );
		
		$this->_joinFields [$alias] = array ('table' => false, 'field' => $fullExpression );
		return $this;
	}
        
        /**
         * Add customer email to select
         * return $this
         */
        public function selectCustomerEmail()
        {
            $this->getSelect()->join(
                array('customer_entity' => Mage::getSingleton('core/resource')->getTableName('customer_entity')), 
                'main_table.customer_id = customer_entity.entity_id', 
                array('email')
            );
            
            return $this;
        }
        
        /**
         * Add increment ID when the reason ID is matching an order
         * return $this
         */
        public function selectIncrementIdOnOrders()
        {
            $orderReasonId = Mage::helper('rewards/transfer_reason')->getReasonId('order');
            $this->getSelect()->joinLeft(
                array('sales_order' => Mage::getSingleton('core/resource')->getTableName('sales/order')), 
                "main_table.reason_id = {$orderReasonId} AND main_table.reference_id = sales_order.entity_id", 
                array('increment_id')
            );
            
            return $this;
        }
	
	/**
	 * Adds the full customer name to the query.
	 *
	 * @param string|$alias What to name the column
	 * @return TBT_Rewards_Model_Mysql4_Transfer_Collection
	 */
	public function addFullCustomerNameFilter($filter) {
		$this->selectCustomerName ();
		
		$fields = array ();
		$fields ['firstname'] = 'firstname';
		$fields ['lastname'] = 'firstname';
		
		$expr = 'CONCAT(' . (isset ( $fields ['prefix'] ) ? 'IF({{prefix}} IS NOT NULL AND {{prefix}} != "", CONCAT({{prefix}}," "), ""),' : '') . '{{firstname}}' . (isset ( $fields ['middlename'] ) ? ',IF({{middlename}} IS NOT NULL AND {{middlename}} != "", CONCAT(" ",{{middlename}}), "")' : '') . '," ",{{lastname}}' . (isset ( $fields ['suffix'] ) ? ',IF({{suffix}} IS NOT NULL AND {{suffix}} != "", CONCAT(" ",{{suffix}}), "")' : '') . ')';
		
		$expr = str_replace ( "{{firstname}}", "customer_firstname_table.value", $expr );
		$expr = str_replace ( "{{lastname}}", "customer_lastname_table.value", $expr );
		
		$fullExpression = $expr;
		//$this->getSelect()->where($fullExpression, array('LIKE' => "%".$filter));
		

		return $this;
	}
	
	/**
	 * Adds the full customer name to the query.
	 *
	 * @param string|$alias What to name the column
	 * @return TBT_Rewards_Model_Mysql4_Transfer_Collection
	 */
	public function selectPointsCaption($alias = 'points') {
		$this->selectCurrency ();
		
		$expr = 'CONCAT({{quantity}}, \' \', {{currency_caption}})';
		
		$expr = str_replace ( "{{currency_caption}}", "currency_table.caption", $expr );
		$expr = str_replace ( "{{quantity}}", "main_table.quantity", $expr );
		
		$fullExpression = $expr;
		
		$this->getSelect ()->from ( null, array ($alias => $fullExpression ) );
		
		$this->_joinFields [$alias] = array ('table' => false, 'field' => $fullExpression );
		return $this;
	}
	
	/**
	 * Fetches only transfers that give points to the customer
	 *
	 * @return TBT_Rewards_Model_Mysql4_Transfer_Collection
	 */
	public function selectOnlyPosTransfers() {
		$this->addFieldToFilter ( 'quantity', array ('gt' => 0 ) );
		return $this;
	}
	
	/**
	 * Fetches only transfers that deduct points from the customer
	 *
	 * @return TBT_Rewards_Model_Mysql4_Transfer_Collection
	 */
	public function selectOnlyNegTransfers() {
		$this->addFieldToFilter ( 'quantity', array ('lt' => 0 ) );
		return $this;
	}
	
	public function selectOnlyActive() {
		$countableStatusIds = Mage::getSingleton ( 'rewards/transfer_status' )->getCountableStatusIds ();
		$this->getSelect ()->where ( 'main_table.status_id IN (?)', $countableStatusIds );
		
		return $this;
	}
        
    /**
     * Filter transfers that are not expiry system adjustments
     * @return \TBT_Rewards_Model_Mysql4_Transfer_Collection
     */
    public function selectNonExpiryTransfers()
    {
        $this->addFieldToFilter(
            'reason_id',
            array('neq' => Mage::helper('rewards/transfer_reason')->getReasonId('expire'))
        );
        
        return $this;
    }
    
    /**
     * Filter transfers that are expiry system adjustments
     * @return \TBT_Rewards_Model_Mysql4_Transfer_Collection
     */
    public function selectExpiryTransfers()
    {
        $this->addFieldToFilter(
            'reason_id',
            array('eq' => Mage::helper('rewards/transfer_reason')->getReasonId('expire'))
        );
        
        return $this;
    }
	
	/**
	 * Filters transfers with a CANCELLED status out of the SQL query
	 * @return self
	 */
	public function excludeCancelledTransfers()
	{
		$this->addFieldToFilter('status_id', array('neq' => TBT_Rewards_Model_Transfer_Status::STATUS_CANCELLED));
		return $this;
	}
	
	/**
	 * Sums up the points by currency and grouped again by customer.
	 *
	 * @return TBT_Rewards_Model_Mysql4_Transfer_Collection 
	 */
	public function groupByCustomers() {
		$this->selectCurrency ();
		
		$this->getSelect ()->group ( 'main_table.customer_id' );
		$this->sumPoints ();
		$this->getSelect ()->from ( null, array ("points" => "CONCAT(SUM(main_table.quantity), ' ', currency_table.caption)" ) );
		$this->getSelect ()->from ( null, array ("last_changed_ts" => "MAX(main_table.created_at)" ) );
		
		return $this;
	}
	
	public function groupByCurrency() {
		return $this->sumPoints ();
	}
	
	/**
	 * Sums up the points in the collection as the "points_count" field for
	 * each currency.
	 * <b>Please use the 'points_count' field instead of the quantity field</b>
	 * 
	 * @return TBT_Rewards_Model_Mysql4_Transfer_Collection
	 *
	 */
	public function sumPoints() {
		$this->getSelect ()->from ( null, array ("points_count" => "SUM(main_table.quantity)" ) );
		$this->addExpressionFieldToSelect('transfer_ids', "GROUP_CONCAT(main_table.rewards_transfer_id)", array());
		return $this;
	}
	
	/**
     * Add attribute expression (SUM, COUNT, etc)
     * Example: ('sub_total', 'SUM({{attribute}})', 'revenue')
     * Example: ('sub_total', 'SUM({{revenue}})', 'revenue')
     * For some functions like SUM use groupByAttribute.
     *
     * @param string $alias
     * @param string $expression
     * @param array $fields
     * @return Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    public function addExpressionFieldToSelect($alias, $expression, $fields)
    {
        // validate alias
        if (!is_array($fields)) {
            $fields = array($fields=>$fields);
        }

        $fullExpression = $expression;
        foreach ($fields as $fieldKey=>$fieldItem) {
            $fullExpression = str_replace('{{' . $fieldKey . '}}', $fieldItem, $fullExpression);
        }

        $this->getSelect()->columns(array($alias=>$fullExpression));

        return $this;
    }

	/**
	 * Returns a NEW collection, composed of all transfers that have revoked
	 * any of the transfers in the current collection.
	 * @return self
	 */
	public function selectRevokerTransfers()
	{
		$transferIds = $this->getTransferIds();
		$revokers = Mage::getResourceModel('rewards/transfer_collection');
		$revokers->addFieldToFilter('reason_id', array('eq' => Mage::helper('rewards/transfer_reason')->getReasonId('revoke')))
			->addFieldToFilter('reference_id', array('in' => $transferIds));
		return $revokers;
	}
	
	/**
	 * Returns an array of ID's from this collection if it has more than one
	 * item in it or if it has a single item which doesn't have a transfer_ids
	 * value.  The transfer_ids value is generated when sumPoints() is called
	 * since that method groups rows together, losing context of which transfers
	 * made up the sum.
	 * @return array An array of ID's from this collection (even if GROUP'd)
	 */
	public function getTransferIds()
	{
		if ($this->count() > 1) {
			return $this->getAllIds();
		}
		
		$firstItem = $this->getFirstItem();
		if (!$firstItem->getTransferIds()) {
			return $this->getAllIds();
		}
		
		return explode(',', $firstItem->getTransferIds());
	}
	
	/**
	 * Using the parent's getAllIds() if collection hasn't been loaded yet (since
	 * the parent version loads a new collection, composed of ONLY ID's) but
	 * reverting to the original getAllIds() which checks the current collection,
	 * if it's already been loaded.
	 * @return array An array of ID's from this collection
	 */
	public function getAllIds()
	{
		if (!$this->isLoaded()) {
			return parent::getAllIds();
		}
		
		$ids = array();
		foreach ($this->getItems() as $item) {
			$ids[] = $this->_getItemId($item);
		}
		return $ids;
	}
	
	/**
	 * True if the collection only contains zero-point transfers (for some reason)
	 * or if the summed point quantities are zero for all currencies
	 * or if the collection does not contain any transfers.
	 *
	 * @return boolean
	 */
	public function isNoPoints() {
		foreach ( $this->getItems () as $item ) {
			if (isset ( $item ['points_count'] )) {
				if ($item ['points_count'] > 0) {
					return false;
				}
			} elseif (isset ( $item ['quantity'] )) {
				if ($item ['quantity'] > 0) {
					return false;
				}
			} else {
				// should never get here...	
			}
		}
		return true;
	}
	
	/**
	 * Adds an 'absolute_quantity' alias to the query and orders by it.
	 * @param int $direction
	 */
	public function sortByAbsoluteQuantity($direction = self::SORT_ORDER_DESC)
	{
		$this->addExpressionFieldToSelect('absolute_quantity', "ABS({{quantity}})", 'quantity');
		$this->setOrder('absolute_quantity', $direction);
		return $this;
	}
        
    /**
     * Prepare Points Activity Collection Based on points type and range
     * @param string $range
     * @param string $type
     * @return \TBT_Rewards_Model_Mysql4_Transfer_Collection
     */
    public function prepareMetricsPointsActivity($range, $type = 'earn')
    {
        if ($type == 'spend') {
            $this->selectOnlyNegTransfers();
            $this->selectNonExpiryTransfers();
        } else {
            $this->selectOnlyPosTransfers();
        }

        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();
        
        $select = $this->getSelect();
        $select->join(new Zend_Db_Expr('(SELECT @aux := 0)'), '');

        $select->reset(Zend_Db_Select::COLUMNS);

        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($range, 0, 0);

        $select->columns(array(
            'points_quantity' => new Zend_Db_Expr(
                '@aux := @aux + SUM(ABS(quantity))'
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
     * Getter for Total Points Value before range
     * @param string $range
     * @param string $type
     * @return int|float
     */
    public function getTotalPointsActivityBeforePeriod($range, $type = 'earn')
    {
        if ($type == 'spend') {
            $this->selectOnlyNegTransfers();
            $this->selectNonExpiryTransfers();
        } else {
            $this->selectOnlyPosTransfers();
        }

        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();
        
        $select = clone $this->getSelect();

        $select->reset(Zend_Db_Select::COLUMNS);

        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($range, 0, 0);

        $select->columns(array(
            'points_quantity' => new Zend_Db_Expr(
                'SUM(ABS(quantity))'
            )
        ));
        
        $select->where("DATE_FORMAT(DATE_ADD(created_at, INTERVAL $gmtOffset SECOND), '%Y-%m-%d') < '" . $dateRange['from']->toString('Y-MM-dd') . "'");
        
        $connection = $this->getConnection();
        $result = $connection->fetchRow($select);
        
        return (float) $result['points_quantity'];
    }
    
    /**
     * Getter for Total Points Value before range
     * @param string $range
     * @param string $type
     * @return int|float
     */
    public function getTotalPointsActivityByDate($startDate, $after = true)
    {
        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();
        
        $select = clone $this->getSelect();
        $select->reset(Zend_Db_Select::COLUMNS);

        $select->columns(array(
            'points_quantity' => new Zend_Db_Expr(
                'SUM(ABS(quantity))'
            )
        ));
        
        if ($startDate) {
            $operand = ($after) ? '>=' : '<';
            $select->where("DATE_FORMAT(DATE_ADD(created_at, INTERVAL $gmtOffset SECOND), '%Y-%m-%d') ". $operand ." '" . $startDate . "'");
        }
        
        $connection = $this->getConnection();
        $result = $connection->fetchRow($select);
        
        return (float) $result['points_quantity'];
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
                    ->getStoreTZOffsetQuery($this->getMainTable(), $attribute, $from, $to),
            $this->_getRangeExpression($range)
        );
    }
}
