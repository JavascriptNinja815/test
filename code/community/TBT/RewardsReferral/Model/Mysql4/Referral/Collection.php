<?php

class TBT_RewardsReferral_Model_Mysql4_Referral_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    protected $_includeCustomerData = true;

    public function _construct()
    {
        $this->_init('rewardsref/referral');
    }

    protected function _beforeLoad()
    {
        if ($this->_includeCustomerData) {
            $this->getSelect()->join(
                    array('cust' => $this->getTable('customer/entity')), 'referral_parent_id = cust.entity_id'
            );
        }

        return parent::_beforeLoad();
    }

    public function excludeCustomerData()
    {
        $this->_includeCustomerData = false;
        return $this;
    }

    public function includeCustomerData()
    {
        $this->_includeCustomerData = true;
        return $this;
    }

    /**
     * Will filter collection to match records
     * where "referral_child_id" or "referral_email" match supplied arguments
     *
     * @param $referredCustomerId
     * @param $referredCustomerEmail
     * @return $this
     *
     */
    public function addIdOrEmailFilter($referredCustomerId, $referredCustomerEmail)
    {
        $this->getSelect()
            ->where("referral_child_id = '{$referredCustomerId}' OR referral_email = '{$referredCustomerEmail}'");

        return $this;
    }

    public function addEmailFilter($email, $websiteId = null)
    {
        $this->getSelect()->where('referral_email = ?', $email);

        if ($websiteId) {
            $this->getSelect()->where('`cust`.`website_id` = ?', (int) $websiteId);
        }

        return $this;
    }

    public function addFlagFilter($status)
    {
        $this->getSelect()->where('referral_status = ?', $status);
        return $this;
    }

    public function addClientFilter($id)
    {
        $this->getSelect()->where('referral_parent_id = ?', $id);
        return $this;
    }

    public function addParentNameToSelect()
    {
        $firstname      = Mage::getResourceSingleton('customer/customer')->getAttribute('firstname');
        $lastname       = Mage::getResourceSingleton('customer/customer')->getAttribute('lastname');
        $fullExpression = new Zend_Db_Expr("CONCAT(customer_firstname_table.value,' ',customer_lastname_table.value)");

        $this->getSelect()->joinLeft(
            array('customer_lastname_table' => $lastname->getBackend()->getTable()),
            'customer_lastname_table.entity_id = main_table.referral_parent_id
             AND customer_lastname_table.attribute_id = '.(int) $lastname->getAttributeId() . '
             ',
            array()
        )
        ->joinLeft(
            array('customer_firstname_table' =>$firstname->getBackend()->getTable()),
            'customer_firstname_table.entity_id = main_table.referral_parent_id
             AND customer_firstname_table.attribute_id = '.(int) $firstname->getAttributeId() . '
             ',
            array()
        )
        ->columns(array("parent_name" => $fullExpression));

        $this->getSelect ()->from ( null, array ("parent_name" => $fullExpression ) );
        $this->_joinFields ["parent_name"] = array ('table' => false, 'field' => $fullExpression );

        return $this;
    }
    
    /**
     * Prepare Referral Signups Collection based on range
     * @param string $range
     * @return \TBT_RewardsReferral_Model_Mysql4_Referral_Collection
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
                '@aux := @aux + COUNT(rewardsref_referral_id)'
            ),
            'range' => new Zend_Db_Expr(
                "MAX(".$this->_getRangeExpression($range, "DATE_ADD(created_ts, INTERVAL $gmtOffset SECOND)").")"
            )
        ));
        
        $select->group($this->_getRangeExpression($range, "DATE_ADD(created_ts, INTERVAL $gmtOffset SECOND)"));

        $select->where("DATE_FORMAT(DATE_ADD(created_ts, INTERVAL $gmtOffset SECOND), '%Y-%m-%d') >= '" . $dateRange['from']->toString('Y-MM-dd') . "'");
        $select->where("referral_status != " . TBT_RewardsReferral_Model_Referral::STATUS_REFERRAL_SENT);
        
        return $this;
    }
    
    /**
     * Getter for Total Referral Signups before range
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
                'COUNT(rewardsref_referral_id)'
            )
        ));
        
        $select->where("DATE_FORMAT(DATE_ADD(created_ts, INTERVAL $gmtOffset SECOND), '%Y-%m-%d') < '" . $dateRange['from']->toString('Y-MM-dd') . "'");
        $select->where("referral_status != " . TBT_RewardsReferral_Model_Referral::STATUS_REFERRAL_SENT);
        
        $connection = $this->getConnection();
        $result = $connection->fetchRow($select);
        
        return (float) $result['quantity'];
    }
    
    /**
     * Getter for Total Referral Signups by Date
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
                'COUNT(rewardsref_referral_id)'
            )
        ));
        
        if ($startDate) {
            $operand = ($after) ? '>=' : '<';
            $select->where("DATE_FORMAT(DATE_ADD(created_ts, INTERVAL $gmtOffset SECOND), '%Y-%m-%d') ". $operand ." '" . $startDate . "'");
        }
        
        $select->where("referral_status != " . TBT_RewardsReferral_Model_Referral::STATUS_REFERRAL_SENT);
        
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
                    ->getStoreTZOffsetQuery($this->getMainTable(), $attribute, $from, $to),
            $this->_getRangeExpression($range)
        );
    }

}
