<?php

class TBT_Rewards_Model_Mysql4_Metrics_Earnings_Collection extends TBT_Rewards_Model_Mysql4_Metrics_Collection_Abstract
{

    /**
     * Total Amount of Points Earned
     *
     * @var int
     **/
    protected $_totalEarnedPoints;

    /**
     * Initialize custom resource model
     */
    public function __construct()
    {
        parent::_construct();

        $this->setModel('adminhtml/report_item');
        $this->_resource = Mage::getResourceModel('rewards/metrics')->init('rewards/transfer');
        $this->setConnection($this->getResource()->getReadConnection());

        return $this;
    }

    /**
     * Add selected data.
     *
     * @return TBT_Rewards_Model_Mysql4_Metrics_Earnings_Collection
     */
    protected function _initSelect()
    {
        $select  = $this->getSelect();
        $adapter = $this->getConnection();

        $subSelect = $adapter->select()
            ->from(array('transfer_table' => $this->getTable('rewards/transfer')), array(
                'transfer_table.customer_id', 
                'transfer_table.quantity', 
                'transfer_table.created_at', 
                'transfer_table.status_id', 
                'transfer_table.reason_id'))
            ->where('quantity > 0');

        $select->reset(Zend_Db_Select::FROM)
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array('main_table' => $subSelect), $this->_getSelectedColumns());

        if (!$this->isTotals()) {
            $select->group(array($this->_periodFormat, 'reason_id'));
        } elseif ($this->isChart()) {
            $select->group(array('reason_id'));
        }

        return $this;
    }

    /**
     * [_getSelectedColumns description]
     *
     * @return array
     */
    protected function _getSelectedColumns()
    {
        if ($this->_selectedColumns) {
            return $this->_selectedColumns;
        }

        $this->_setPeriodFormat($this->_period);

        if ($this->isTotals()) {
            $this->_selectedColumns = $this->getAggregatedColumns();
        } else {
            $this->_selectedColumns['period'] = $this->_periodFormat;
        }

        $this->_selectedColumns['reason_id'] = 'reason_id';
        $this->_selectedColumns['customer_id']         = 'customer_id';
        $this->_selectedColumns['total_points']        = 'SUM(quantity)';

        if ($this->isChart()) {
            $totalEarnedPoints = $this->_getTotalEarnedPoints();
            $this->_selectedColumns['points_percentage'] = sprintf('(SUM(quantity) / %d) * 100', $totalEarnedPoints);
        }

        return $this->_selectedColumns;
    }

    public function prepareSummary($period, $storeIds, $from = null, $to = null, $transferStatus)
    {
        parent::prepareSummary($period, $storeIds, $from, $to, $transferStatus);
        $this->isChart(true)
            ->isTotals(true);

        return $this;
    }

    /**
     * Retrieves the total amount of points earned in the selected period.
     *
     * @return int
     */
    public function _getTotalEarnedPoints()
    {
        if (!is_null($this->_totalEarnedPoints)) {
            return $this->_totalEarnedPoints;
        }

        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from(array('transfers' => $this->getTable('rewards/transfer')))
            ->reset(Zend_Db_Select::COLUMNS)
            ->where('quantity > 0')
            ->columns('SUM(quantity)');

        // apply date range filter if needed
        if ($this->_from !== null) {
            $select->where('created_at >= ?', $this->_from);
        }
        if ($this->_to !== null) {
            $select->where('created_at <= ?', $this->_to);
        }

        $this->_totalEarnedPoints = $adapter->fetchOne($select);

        return $this->_totalEarnedPoints;
    }

}
