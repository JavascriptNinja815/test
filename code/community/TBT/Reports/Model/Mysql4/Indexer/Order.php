<?php

class TBT_Reports_Model_Mysql4_Indexer_Order extends Mage_Index_Model_Mysql4_Abstract
{
    // Allow a 10 second buffer for order-date calculations
    const ORDER_DATE_OFFSET_SECONDS = 10;

    protected function _construct()
    {
        $this->_init('tbtreports/indexer_order', 'order_id');
        $this->_isPkAutoIncrement = false;
        return $this;
    }

    /**
     * Called by Indexer model when an Order place_after event comes through.
     * @param Mage_Index_Model_Event $event
     */
    public function orderPlaceAfter($event)
    {
        $dataObject = $event->getDataObject();
        $newData = $event->getNewData();

        // If there's no customer, there's not much for us to do.
        if (empty($newData['customer_id'])) {
            Mage::getModel('tbtreports/indexer_order')
                ->setOrderId($newData['order_id'])
                ->save();

        } else if ($dataObject instanceof Mage_Sales_Model_Order) {
            $this->_reindexEntity($dataObject);

        } else {
            $order = Mage::getModel('sales/order')->load($newData['order_id']);
            $this->_reindexEntity($order);
        }
    }

    /**
     * Will accept an order object and create an index record for it accordingly
     * @param Mage_Sales_Model_Order $order
     */
    protected function _reindexEntity($order)
    {
        $index = Mage::getModel('tbtreports/indexer_order')
            ->setOrderId($order->getId());

        $customer = $order->getCustomer();
        if (!$customer) {
            if (!$order->getCustomerId()) {
                $customer = Mage::getModel('customer/customer');
                $customer->setWebsiteId(Mage::app()->getStore($order->getStoreId())->getWebsiteId());
                $customer->loadByEmail($order->getCustomerEmail());
            } else {
                $customer = Mage::getModel('customer/customer')->load($order->getCustomerId());
            }

            if (!$customer || !$customer->getId()) {
                $index->save();
                return;
            }
        }

        $orderDate = $order->getCreatedAt();
        $customer = Mage::getModel('tbtreports/customer')->setCustomerObject($customer);
        $wasLoyal = $customer->wasLoyal($orderDate, self::ORDER_DATE_OFFSET_SECONDS);
        $wasReferred = $wasLoyal ? $customer->wasReferred($orderDate, self::ORDER_DATE_OFFSET_SECONDS) : false;

        $index->setCustomerId($customer->getId())
            ->setByLoyaltyCustomer($wasLoyal)
            ->setByReferredCustomer($wasReferred)
            ->save();
    }

    /**
     * Called when an admin triggers a reindex through the admin panel.
     * Will reindex all orders placed 30 days ago from right now except for any which have already been indexed.
     */
    public function reindexAll()
    {
        $_30daysAgo = $this->_getDateTimeHelper()->xDaysAgo(30, false, true);

        $startTime = $this->_recordPerformance(null, $_30daysAgo);
        $this->_reindexOrders($_30daysAgo);
        $endTime = $this->_recordPerformance($startTime, $_30daysAgo);
    }


    /**
     * Will process orders in batches of 1000 and create index records for each.
     * @param string $startDateToIndex. MySQL date string in UTC time for orders to process.
     * @param string $endDateToIndex.MySQL date string in UTC time for orders to process.
     */
    public function _reindexOrders($startDateToIndex = null, $endDateToIndex = null)
    {
        $count = 0;
        $orders = $this->_getOrderCollection()
            ->excludeIndexedOrders(true)
            ->limitPeriod($startDateToIndex, $endDateToIndex);

        $orders->setPageSize(1000);
        $pages = $orders->getLastPageNumber();
        $currentPage = 1;
        do {
            // Always load 1st page because source table keeps changing so we should avoid offsets.
            $orders->setCurPage(1);
            $orders->load();
            foreach ( $orders as $order ) {
                $count++;
                $this->_reindexEntity($order);
            }

            //clear collection and free memory
            $currentPage++;
            $orders->clear();
        } while ($currentPage <= $pages);
    }


    public function save(Mage_Core_Model_Abstract $object)
    {
        try {
            parent::save($object);

        } catch (Exception $e) {
            // @todo: catch duplicate key errors
            Mage::helper('rewards/debug')->logException($e);
        }
    }

    /**
     * Returns the index table name
     *
     * @see Mage_Index_Model_Mysql4_Abstract::getIdxTable()
     * @param null $table (doesn't do anything)
     * @return string
     */
    public function getIdxTable($table = null)
    {
        return $this->getMainTable();
    }


    /**
     * Will record indexing process time as well as number of orders processed
     * in tbtreports.log file if recording indexer performance is enabled.
     *
     * @see TBT_Reports_Helper_Data::shouldRecordIndexerPerformance()
     * @param int|null $processStartTime. If not supplied, assumes start of process
     * @param string $startDateToIndex
     * @return int unix time when this funciton was called.
     */
    protected function _recordPerformance($processStartTime = null, $startDateToIndex = null, $endDateToIndex = null)
    {
        $now = time();
        $shouldRecordPerformance = $this->_getHelper()->shouldRecordIndexerPerformance();
        if (!$shouldRecordPerformance) {
            return $now;
        }

        $orders = Mage::getModel('sales/order')->getCollection();
        if ($startDateToIndex) $orders->addFieldToFilter('created_at', array('gteq' => $startDateToIndex));
        if ($endDateToIndex) $orders->addFieldToFilter('created_at', array('lteq' => $endDateToIndex));
        $ordersCount = $orders->getSize();

        if (!$processStartTime) {
            if ($startDateToIndex)                      $dateString = "between {$startDateToIndex} & now";
            if ($endDateToIndex)                        $dateString = "all before {$endDateToIndex}";
            if ($startDateToIndex && $endDateToIndex)   $dateString = "between {$startDateToIndex} & {$endDateToIndex}";
            if (!$startDateToIndex && !$endDateToIndex) $dateString = "of all time.";
            $report = "Starting to index {$ordersCount} orders {$dateString} ...";

        } else {
            $processEndTime = $now;
            $processTime = ((int) $processEndTime) - ((int) $processStartTime);
            $report = "  Finished indexing {$ordersCount} orders in {$processTime} seconds.";
        }

        Mage::log($report, null ,"tbtreports.log" , true);
        return $now;
    }

    /**
     * Insert rows in select statement into index table
     * @param Varien_Db_Select $select
     * @return $this
     */
    protected function _insertSelect($select)
    {
        $writeAdapter = $this->_getWriteAdapter();
        $query = $writeAdapter->insertFromSelect($select, $this->getIdxTable());
        $writeAdapter->query($query);

        return $this;
    }

    /**
     * @return TBT_Reports_Model_Mysql4_Order_Collection
     */
    protected function _getOrderCollection()
    {
        return Mage::getResourceModel('tbtreports/order_collection');
    }

    /**
     * @return TBT_Reports_Model_Mysql4_Customer_Collection
     */
    protected function _getCustomerCollection()
    {
        return Mage::getResourceModel('tbtreports/customer_collection');
    }

    /**
     * @return TBT_Rewards_Helper_Datetime
     */
    public function _getDateTimeHelper()
    {
        return Mage::helper('rewards/datetime');
    }

    /**
     * @return TBT_Reports_Helper_Data
     */
    public function _getHelper()
    {
        return Mage::helper('tbtreports');
    }

    /**
     * Will force log a message to tbtreports.log
     * @param $message
     * @return $this
     */
    protected function _log($message)
    {
        Mage::log($message, null ,"tbtreports.log" , true);
        return $this;
    }
}
