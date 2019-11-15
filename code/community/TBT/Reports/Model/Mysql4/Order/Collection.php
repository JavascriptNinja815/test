<?php

class TBT_Reports_Model_Mysql4_Order_Collection extends Mage_Reports_Model_Mysql4_Order_Collection
{
    const TABLE_ALIAS = 'orders_table';

    protected $_size = false;
    protected $_flags = array();

    /**
     * Sales amount expression
     * @var string
     */
    protected $_salesAmountExpression;

    /**
     * Limit the collection to orders placed by specified customers only.
     *
     * @param int|array|Varien_Db_Select $customerIds
     * @return $this
     */
    public function filterByCustomerId($customerIds)
    {
        if (is_numeric($customerIds)) $customerIds = array($customerIds);
        $this->addFieldToFilter('customer_id', array('in' => $customerIds));

        return $this;
    }

    /**
     * Exclude orders which are new or pending payment
     * @return $this
     */
    public function onlyCompleteOrders()
    {
        $this->_flags['only_complete_orders'] = true;
        $this->addFieldToFilter('state', array('in' => array(
            Mage_Sales_Model_Order::STATE_COMPLETE
        )));

        return $this;
    }

    /**
     * Will exclude any orders which have already been indexed by this module
     *
     * @param boolean $useJoin (default: false). If true, will use a Left-Join on the index
     * table to exclude orders which have already been processed. Usually faster if index table is small or empty.
     * If false, will use a Sub-query and NOT-IN condition to exclude orders which have already been indexed.
     * Usually faster if index table contains a lot of values already.
     *
     * @return $this
     */
    public function excludeIndexedOrders($useJoin = false)
    {
        $this->_flags['exclude_indexed_orders'] = true;

        if ($useJoin) {
            // USE JOIN
            $this->_flags['joined_on_index'] = true;
            $this->getSelect()->joinLeft(
                array('index_table' => $this->getTable('tbtreports/indexer_order')),
                "`" . self::TABLE_ALIAS . "`.`entity_id` = `index_table`.`order_id`", ''
            )->where('`index_table`.`order_id` is null');

        } else {
            // Use NOT IN
            $indexedOrders = $this->_getIndexerCollection()->getOrderIds();
            $this->addFieldToFilter('entity_id', array('nin' => $indexedOrders));
        }

        return $this;
    }

    /**
     * Will filter collection to return orders placed by loyalty customers only
     * @return $this
     * @throws Exception if tbtreports/indexer_order is not ready
     */
    public function onlyOrdersByLoyaltyCustomers($checkIndexer = true)
    {
        if ($checkIndexer) {
            $this->_checkIndexer();
        }

        $this->_flags['only_orders_by_loyalty_customers'] = true;
        $this->_flags['joined_on_index'] = true;
        $this->getSelect()->joinInner(
            array('index_table' => $this->getTable('tbtreports/indexer_order')),
            "index_table.by_loyalty_customer = 1 && ".
            "`" . self::TABLE_ALIAS . "`.`entity_id` = `index_table`.`order_id`",''
        );

        return $this;
    }

    /**
     * Will filter collection to return orders placed by referred customers only
     * @return $this
     * @throws Exception if tbtreports/indexer_order is not ready
     */
    public function onlyOrdersByReferredCustomers($checkIndexer = true)
    {
        if ($checkIndexer) {
            $this->_checkIndexer();
        }
        
        $this->_flags['only_orders_by_referred_customers'] = true;
        $this->_flags['joined_on_index'] = true;
        $this->getSelect()->joinInner(
            array('index_table' => $this->getTable('tbtreports/indexer_order')),
            "index_table.by_referred_customer = 1 && ".
            "`" . self::TABLE_ALIAS . "`.`entity_id` = `index_table`.`order_id`",''
        );

        return $this;
    }


    /**
     * Will add start and end dates to the query to filter transaction's by created time
     *
     * @param string $startDate (optional) in UTC time
     * @param string $endDate (optional) in UTC time
     * @return $this
     */
    public function limitPeriod($startDate = null, $endDate = null)
    {
        if ($startDate) {
            $this->_flags['has_start_date'] = true;
            $this->addFieldToFilter('created_at', array('gteq' => $startDate));
        }

        if ($endDate) {
            $this->_flags['has_end_date'] = true;
            $this->addFieldToFilter('created_at', array('lteq' => $endDate));
        }

        return $this;
    }


    /**
     * Will return total revenue from all orders in the collection
     * @return float
     */
    public function getTotalRevenue()
    {
        $connection = $this->getConnection();
        $select = clone $this->getSelect();
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->columns(array(
            'revenue' => new Zend_Db_Expr(
                sprintf('SUM(GREATEST((%s) * %s, 0))', $this->_getSalesAmountExpression(),
                    $this->getIfNullSql(self::TABLE_ALIAS . '.base_to_global_rate', 0)
                )
            ),
            'total_orders' => 'count(*)',
        ));

        $result = $connection->fetchRow($select);
        $this->_size = (int) $result['total_orders'];
        return (float) $result['revenue'];
    }
    
    /**
     * Prepare Revenue Metrics Collection
     * @return float
     */
    public function prepareMetricsRevenue($range)
    {
        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();

        $select = $this->getSelect();
        $select->join(new Zend_Db_Expr('(SELECT @aux := 0)'), '');

        $select->reset(Zend_Db_Select::COLUMNS);

        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($range, 0, 0);

        $select->columns(array(
            'revenue' => new Zend_Db_Expr(
                sprintf('SUM(GREATEST((%s) * %s, 0))', $this->_getSalesAmountExpression(),
                    $this->getIfNullSql(self::TABLE_ALIAS . '.base_to_global_rate', 0)
                )
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
     * Return Total Revenue Before Range Date
     * @return float
     */
    public function getTotalRevenueBeforePeriod($range)
    {
        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();

        $select = clone $this->getSelect();
        
        $select->reset(Zend_Db_Select::COLUMNS);

        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($range, 0, 0);

        $select->columns(array(
            'revenue' => new Zend_Db_Expr(
                sprintf('SUM(GREATEST((%s) * %s, 0))', $this->_getSalesAmountExpression(),
                    $this->getIfNullSql(self::TABLE_ALIAS . '.base_to_global_rate', 0)
                )
            )
        ));
        
        $select->where("DATE_FORMAT(DATE_ADD(created_at, INTERVAL $gmtOffset SECOND), '%Y-%m-%d') < '" . $dateRange['from']->toString('Y-MM-dd') . "'");
        
        $connection = $this->getConnection();
        $result = $connection->fetchRow($select);
        
        return (float) $result['revenue'];
    }
    
    /**
     * Return Total Revenue by Date
     * @return float
     */
    public function getTotalRevenueByDate($startDate = null, $after = true)
    {
        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();

        $select = clone $this->getSelect();
        $select->reset(Zend_Db_Select::COLUMNS);

        $select->columns(array(
            'revenue' => new Zend_Db_Expr(
                sprintf('SUM(GREATEST((%s) * %s, 0))', $this->_getSalesAmountExpression(),
                    $this->getIfNullSql(self::TABLE_ALIAS . '.base_to_global_rate', 0)
                )
            )
        ));
        
        if ($startDate) {
            $operand = ($after) ? '>=' : '<';
            $select->where("DATE_FORMAT(DATE_ADD(created_at, INTERVAL $gmtOffset SECOND), '%Y-%m-%d') ". $operand ." '" . $startDate . "'");
        }
        
        $connection = $this->getConnection();
        $result = $connection->fetchRow($select);
        
        return (float) $result['revenue'];
    }
   
    /**
     * Prepare Orders Repeat Purchase Count Metrics
     * @return float
     */
    public function prepareMetricsOrdersRepeatPurchase($range)
    {
        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();
        
        $nthOrder = 2;
        $select = $this->getSelect();

        $whereTableAlias = self::TABLE_ALIAS . '_aux';
        $whereSelect = clone $select;
        $whereSelect->reset();
        $whereSelect->from(
            array(
                $whereTableAlias => $this->getMainTable()
            )
        );
        
        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($range, 0, 0);

        $whereSelect->reset(Zend_Db_Select::COLUMNS);
        $whereSelect->columns(array(
            'nthOrder' => new Zend_Db_Expr(
                "COUNT(" . $whereTableAlias . ".entity_id)"
            ),
        ));
        
        $whereSelect->where(
            new Zend_Db_Expr(
                $whereTableAlias . ".created_at"
                . ' <= ' . self::TABLE_ALIAS . ".created_at"
                . " AND " . $whereTableAlias . ".customer_id = " . self::TABLE_ALIAS . ".customer_id"
            )
        );

        $select->reset(Zend_Db_Select::COLUMNS);
        
        $select->columns(
            array(
                'quantity' => new Zend_Db_Expr(
                    "COUNT(" . self::TABLE_ALIAS . ".customer_id)"
                ),
                'range' => new Zend_Db_Expr(
                    $this->_getRangeExpression($range, self::TABLE_ALIAS . '.created_at')
                )
            )
        );

        $select->where(
            new Zend_Db_Expr(
                "DATE_FORMAT(DATE_ADD(".self::TABLE_ALIAS . ".created_at, INTERVAL $gmtOffset SECOND), '%Y-%m-%d')"
                . " >= '" . $dateRange['from']->toString('Y-MM-dd') . "'"
            )
        );

        $select->where(
            new Zend_Db_Expr(
                $nthOrder . " = (" . new Zend_Db_Expr($whereSelect) . ")"
            )
        );

        $select->where(
            new Zend_Db_Expr(
                self::TABLE_ALIAS . ".customer_id IS NOT NULL"
            )
        );

        $select->group($this->_getRangeExpression($range, "DATE_ADD(" . self::TABLE_ALIAS . '.created_at' . ", INTERVAL $gmtOffset SECOND)"));

        return $this;
    }
    
    /**
     * Getter for Total Customers For Repeat Purchase Orders before Range Date
     * @return float
     */
    public function getTotalOrdersRepeatPurchaseBeforePeriod($range)
    {
        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();
        
        $nthOrder = 2;
        $select = $this->getSelect();

        $whereTableAlias = self::TABLE_ALIAS . '_aux';
        $whereSelect = clone $select;
        $whereSelect->reset();
        $whereSelect->from(
            array(
                $whereTableAlias => $this->getMainTable()
            )
        );

        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($range, 0, 0);

        $whereSelect->reset(Zend_Db_Select::COLUMNS);
        $whereSelect->columns(array(
            'nthOrder' => new Zend_Db_Expr(
                "COUNT(" . $whereTableAlias . ".entity_id)"
            ),
        ));

        $whereSelect->where(
            new Zend_Db_Expr(
                $whereTableAlias . ".created_at"
                . ' <= ' . self::TABLE_ALIAS . ".created_at"
                . " AND " . $whereTableAlias . ".customer_id = " . self::TABLE_ALIAS . ".customer_id"
            )
        );

        $select->reset(Zend_Db_Select::COLUMNS);

        $select->columns(
            array(
                'quantity' => new Zend_Db_Expr(
                    "COUNT(" . self::TABLE_ALIAS . ".customer_id)"
                )
            )
        );

        $select->where(
            new Zend_Db_Expr(
                "DATE_FORMAT(DATE_ADD(".self::TABLE_ALIAS . ".created_at, INTERVAL $gmtOffset SECOND), '%Y-%m-%d')"
                . " < '" . $dateRange['from']->toString('Y-MM-dd') . "'"
            )
        );

        $select->where(
            new Zend_Db_Expr(
                $nthOrder . " = (" . new Zend_Db_Expr($whereSelect) . ")"
            )
        );

        $select->where(
            new Zend_Db_Expr(
                self::TABLE_ALIAS . ".customer_id IS NOT NULL"
            )
        );

        $connection = $this->getConnection();
        $result = $connection->fetchRow($select);
        
        return (int) $result['quantity'];
    }
    
    /**
     * Getter for Total Customers For Repeat Purchase Orders by Date
     * @return float
     */
    public function getTotalOrdersRepeatPurchaseByDate($startDate = null, $after = true)
    {
        $gmtOffset = Mage::getModel('core/date')->getGmtOffset();

        $nthOrder = 2;
        $select = $this->getSelect();

        $whereTableAlias = self::TABLE_ALIAS . '_aux';
        $whereSelect = clone $select;
        $whereSelect->reset();
        $whereSelect->from(
            array(
                $whereTableAlias => $this->getMainTable()
            )
        );

        $dateRange = Mage::helper('tbtreports/adminhtml_metrics_data')
            ->getDateRangeMetrics($range, 0, 0);

        $whereSelect->reset(Zend_Db_Select::COLUMNS);
        $whereSelect->columns(array(
            'nthOrder' => new Zend_Db_Expr(
                "COUNT(" . $whereTableAlias . ".entity_id)"
            ),
        ));

        $whereSelect->where(
            new Zend_Db_Expr(
                $whereTableAlias . ".created_at"
                . ' <= ' . self::TABLE_ALIAS . ".created_at"
                . " AND " . $whereTableAlias . ".customer_id = " . self::TABLE_ALIAS . ".customer_id"
            )
        );

        $select->reset(Zend_Db_Select::COLUMNS);

        $select->columns(
            array(
                'quantity' => new Zend_Db_Expr(
                    "COUNT(" . self::TABLE_ALIAS . ".customer_id)"
                )
            )
        );
        
        if ($startDate) {
            $operand = ($after) ? '>=' : '<';

            $select->where(
                new Zend_Db_Expr(
                    "DATE_FORMAT(DATE_ADD(".self::TABLE_ALIAS . ".created_at, INTERVAL $gmtOffset SECOND), '%Y-%m-%d')"
                    . " " . $operand . " '" . $startDate . "'"
                )
            );
        }
        
        $select->where(
            new Zend_Db_Expr(
                $nthOrder . " = (" . new Zend_Db_Expr($whereSelect) . ")"
            )
        );

        $select->where(
            new Zend_Db_Expr(
                self::TABLE_ALIAS . ".customer_id IS NOT NULL"
            )
        );

        $connection = $this->getConnection();
        $result = $connection->fetchRow($select);

        return (int) $result['quantity'];
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
     * Returns valid IFNULL expression
     *
     * @param Zend_Db_Expr|Zend_Db_Select|string $expression
     * @param string $value OPTIONAL. Applies when $expression is NULL
     * @return Zend_Db_Expr
     */
    public function getIfNullSql($expression, $value = 0)
    {
        if ($expression instanceof Zend_Db_Expr || $expression instanceof Zend_Db_Select) {
            $expression = sprintf("IFNULL((%s), %s)", $expression, $value);
        } else {
            $expression = sprintf("IFNULL(%s, %s)", $expression, $value);
        }

        return new Zend_Db_Expr($expression);
    }

    /**
     * @see Varien_Data_Collection_Db::addFieldToFilter()
     * @param array|string $field
     * @param null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (is_string($field)) $field = self::TABLE_ALIAS . "." . $field;
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * More efficient way to get the number of orders if the collection has been loaded already
     * @return int
     */
    public function getSize()
    {
        if ($this->_size === false) {
            return parent::getSize();
        }

        return $this->_size;
    }

    /**
     * Overwrite to also clear internal flags
     * @return Varien_Data_Collection
     */
    public function clear()
    {
        $this->_flags = array();
        $this->_size = false;
        return parent::clear();
    }

    /**
     * Change default table name
     * @return $this
     */
    protected function _initSelect()
    {
        $this->getSelect()->from(array(
            self::TABLE_ALIAS => $this->getMainTable()
        ));
        return $this;
    }

    /**
     * How to count objects of this collection
     * @return Varien_Db_Select
     * @throws Exception
     */
    public function getSelectCountSql()
    {
        $countSelect = clone $this->getSelect();
        $countSelect->reset(Zend_Db_Select::ORDER);
        $countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $countSelect->reset(Zend_Db_Select::COLUMNS);
        $countSelect->reset(Zend_Db_Select::GROUP);
        $countSelect->reset(Zend_Db_Select::HAVING);
        $countSelect->columns("COUNT(DISTINCT ".self::TABLE_ALIAS.".entity_id)");
        
        return $countSelect;
    }
    
    /**
     * Fetch earliest row entry
     * @return array
     */
    public function getFirstIndexedOrder()
    {
        $select = clone $this->getSelect();
        $select->order('entity_id ASC');
        
        return $this->getConnection()->fetchRow($select);
    }

    /**
     * Overwrites parent expression to change hard-coded table alias name
     * @return mixed
     */
    protected function _getSalesAmountExpression()
    {
        if (is_null($this->_salesAmountExpression)) {
            $adapter = $this->getConnection();
            $expressionTransferObject = new Varien_Object(array(
                'expression' => '%s - %s - %s - (%s - %s - %s)',
                'arguments' => array(
                    $this->getIfNullSql('main_table.base_total_invoiced', 0),
                    $this->getIfNullSql('main_table.base_tax_invoiced', 0),
                    $this->getIfNullSql('main_table.base_shipping_invoiced', 0),
                    $this->getIfNullSql('main_table.base_total_refunded', 0),
                    $this->getIfNullSql('main_table.base_tax_refunded', 0),
                    $this->getIfNullSql('main_table.base_shipping_refunded', 0),
                )
            ));

            Mage::dispatchEvent('sales_prepare_amount_expression', array(
                'collection' => $this,
                'expression_object' => $expressionTransferObject,
            ));
            $this->_salesAmountExpression = vsprintf(
                $expressionTransferObject->getExpression(),
                $expressionTransferObject->getArguments()
            );
        }

        return str_replace('main_table', self::TABLE_ALIAS, $this->_salesAmountExpression);
    }
    
    /**
     * @return TBT_Reports_Model_Mysql4_Indexer_Order_Collection
     */
    protected function _getIndexerCollection()
    {
        return Mage::getResourceModel('tbtreports/indexer_order_collection');
    }

    /**
     * @throws Exception if indexer not ready
     */
    protected function _checkIndexer()
    {
        $helper = Mage::helper('tbtreports/indexer_order');
        $isReady = $helper->isReady();
        if (!$isReady) {
            throw new Exception("Magento index \"". $helper->getName() ."\" is not ready. Please reindex first.");
        }
    }
}
