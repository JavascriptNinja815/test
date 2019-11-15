<?php

class TBT_Reports_Model_Mysql4_Customer_Collection_LoyaltyCustomers extends TBT_Reports_Model_Mysql4_Customer_Collection_Abstract
{
    protected $_skipQueries = false;

    /**
     * Do some checks on the collection and it's flags
     *
     * @throws Exception if there's a problem with the flags set
     * @return Varien_Db_Select
     */
    public function getCustomerIdsSelect()
    {
        $this->checkCollection();

        $startDate = $this->getCollectionFlag('start_date');
        $endDate = $this->getCollectionFlag('end_date');
        $alwaysLoyal = $this->getCollectionFlag('always_loyal_during_period');
        $everLoyal = $this->getCollectionFlag('ever_loyal_during_period');
        $dependsOnOrders = $this->getCollectionFlag('dependent_on_orders');
        $hasExplicitConditions = $alwaysLoyal || $everLoyal || $dependsOnOrders;

        if (
            ((!$startDate || !$endDate) && !$hasExplicitConditions) ||
            (strtotime("{$startDate} + 1 day") < strtotime($endDate) && !$hasExplicitConditions)
        ){
            throw new Exception("
                The definition of Loyalty Customers is very dynamic. A customer who is considered \"loyal\" today, may not be \"loyal\" tomorrow.\n
                For this reason, if your query for loyalty customers exceeds the span of 1 day, you must be explicit about what kind of results you want.\n
                You should call one of the following on the collection:\n\n

                - TBT_Reports_Model_Mysql4_Customer_Collection::everLoyalDuringPeriod()\n
                - TBT_Reports_Model_Mysql4_Customer_Collection::alwaysLoyalDuringPeriod()\n
                - TBT_Reports_Model_Mysql4_Customer_Collection::dependsOnOrderTable()\n\n

                Alternatively, you may limit the period to 1 day only.
            ");
        }

        return $this->_getCustomerIdsSelect();
    }

    /**
     * Apply the current filter to our collection
     */
    public function applyToCollection()
    {
        $this->getCollection()->addFieldToFilter('entity_id', array(
            'in' => $this->getCustomerIdsSelect()
        ));

        // No need for parent collection to apply period limits any more
        $parentCollection = $this->getCollection();
        $parentCollection->shouldApplyPeriodLimits(false);
    }

    /**
     * Returns a selection of loyalty customers who have either of:
     * - has been referred by someone else and signed up
     * - at least one (dev/non-dev) negative points transfer (redemption) which is not a points expiry transfer
     * - at least one record in rewards_milestone_rule_log table where action_type is customergroup (excluding inactivity milestone)
     * - has had two orders with points earnings within 365 days before startDate AND has at least one other earning transfer
     *      (excluding birthday points and points inactivity milestone) within 365 days before startDate.
     * @see TBT_Reports_Model_Customer::wasLoyal()
     * @return Varien_Db_Select
     */
    protected function _getCustomerIdsSelect()
    {
        /**
         * Prep
         */
        $startDate = $this->getCollectionFlag('start_date');
        $endDate = $this->getCollectionFlag('end_date');
        $endDateOffset = $this->getCollectionFlag('end_date_offset');
        if (!empty($endDateOffset)) {
            $endDate = $this->_getDateTimeHelper()->addOffsetToDate($endDate, $endDateOffset);
        }

        /**
         * Figure out some appropriate start and end dates for different batches
         * depending on what we want our results to look like
         */
        $batch3EndDate = $endDate;
        if ($this->getCollectionFlag('dependent_on_orders')) {
            $endDateOffsetAbs = abs($endDateOffset);
            $orderCreatedAtString = '%s.created_at';

            // Start Date for batch3 is always 1 year before an order's created_at date
            $batch3StartDate = new Zend_Db_Expr(sprintf("DATE_SUB({$orderCreatedAtString}, INTERVAL 1 YEAR)", TBT_Reports_Model_Mysql4_Order_Collection::TABLE_ALIAS));

            // End Dates for batch1 and batch2, depend on when the order was placed
            if (is_int($endDateOffset) && $endDateOffset > 0) $orderCreatedAtString = "DATE_ADD({$orderCreatedAtString}, INTERVAL {$endDateOffsetAbs} SECOND)";
            if (is_int($endDateOffset) && $endDateOffset < 0) $orderCreatedAtString = "DATE_SUB({$orderCreatedAtString}, INTERVAL {$endDateOffsetAbs} SECOND)";
            $batch1and2and4EndDates = new Zend_Db_Expr(sprintf($orderCreatedAtString, TBT_Reports_Model_Mysql4_Order_Collection::TABLE_ALIAS));
            $batch3EndDate = $batch1and2and4EndDates;

        } else if ($this->getCollectionFlag('always_loyal_during_period')) {
            // For batch1 and batch2, they would have had to have been "loyal" from start of the period
            // If start of period is beginning of time (null), no one was really "loyal" from then!
            if (is_null($startDate)) $this->_skipQueries = true;
            $batch1and2and4EndDates = $startDate;

            // This will only consider people for Batch 3, if they've been "loyal" the entire time (a full year before $endDate)
            $batch3StartDate = $this->_getDateTimeHelper()->getZendDate($endDate)
                ->sub(1, Zend_Date::YEAR)
                ->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND);


        } else /* if ($this->getCollectionFlag('ever_loyal_during_period')) */ {
            // For batch1and2, as long as it happened at some point before the end of period, we're happy!
            $batch1and2and4EndDates = $endDate;

            // This will consider people for Batch 3, as long as they've been "loyal" at some point during the period (a full year before $startDate)
            $batch3StartDate = null;
            if ($startDate){
                $batch3StartDate = $this->_getDateTimeHelper()->getZendDate($startDate)
                    ->sub(1, Zend_Date::YEAR)
                    ->toString(TBT_Rewards_Helper_Datetime::FORMAT_MYSQL_DATETIME_ZEND);
            }
        }

        /*
         * Batch 1
         * -------
         * Select any customer who's ever had a non-expiry redemption on a live store
         */
        $batch1 = $this->_getTransferCollection()
            ->onlyNegativeTransfers()
            ->excludeExpiryTransfers()
            ->filterTransferMode()
            ->limitPeriod(null, $batch1and2and4EndDates)
            ->getCustomerIds();

        /*
         * Batch 2
         * -------
         * Select any customer who's ever been in a milestone rule where their customer group has changed
         * (except for an inactivity rule)
         */
        $batch2 = $this->_getMilestoneLogCollection()
            ->onlyCustomerGroupActions()
            ->excludeConditionTypes("inactivity")
            ->limitPeriod(null, $batch1and2and4EndDates)
            ->getCustomerIds();

        /*
         * Batch 3
         * -------
         * there's two parts to this (a) & (b),
         *
         *
         * (a)
         * ---
         * Pick anyone who's had earning transaction as a result of placing at least two distinct orders
         * anytime within 1 year preceding supplied $startDate, upto and including supplied $endDate
         */
        $batch3a = $this->_getTransferCollection()
            ->forceJoins()
            ->filterTransferMode()
            ->onlyPositiveTransfers()
            ->onlyForReasons(Mage::helper('rewards/transfer_reason')->getReasonId('order'))
            ->limitPeriod($batch3StartDate, $batch3EndDate);
        $batch3aSelect = $batch3a->prepareCollection()->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns('customer_id')
            ->group('customer_id')
            ->having('COUNT( DISTINCT(reference_id) ) >= 2');

        /*
         * (b)
         * ---
         * Pick anyone who's had at least 1 earning transaction not resulting from:
         * birthday points, inactivity points, or having placed an order,
         * anytime within 1 year preceding supplied $startDate, upto and including supplied $endDate
         */
        $reasonHelper = Mage::helper('rewards/transfer_reason');
        $batch3b = $this->_getTransferCollection()
            ->onlyPositiveTransfers()
            ->filterTransferMode()
            ->excludeReasons(
                $reasonHelper->getReasonId('birthday'),
                $reasonHelper->getReasonId('milestone_inactivity'),
                $reasonHelper->getReasonId('order')
            )
            ->limitPeriod($batch3StartDate, $batch3EndDate);

        /*
         * intersect a & b
         * ---------------
         * Pick any customer who's in both (a) and (b)
         */
        $batch3 = $batch3b->addFieldToFilter('customer_id', array(
                'in' => $batch3aSelect)
        )->getCustomerIds();


        /*
         * Batch 4
         * -------
         * Any customer who has been referred and who has signed-up between startDate and endDate
         */
        $batch4 = Mage::getResourceModel('tbtreports/customer_collection')
                    ->onlyReferredCustomers()
                    ->limitPeriod(null, $batch1and2and4EndDates)
                    ->getCustomerIds();

        /**
         * Finally combine, Batch 1, Batch 2, Batch 3 and Batch 4 if appropriate
         */
        if ($this->_skipQueries) {
            return null;
        }
        $select = $this->getCollection()->getConnection()->select();
        return $select->union(array($batch1, $batch2, $batch3, $batch4));
    }
}