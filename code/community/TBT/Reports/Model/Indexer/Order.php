<?php

class TBT_Reports_Model_Indexer_Order extends Mage_Index_Model_Indexer_Abstract
{
    /**
     * Data key for matching result to be saved in
     */
    const EVENT_MATCH_RESULT_KEY = 'tbtreports_indexer_order_match_result';
    const EVENT_TYPE_ORDER_PLACE_AFTER = 'place_after';

    /**
     * Matched entities list
     * @name _matchedEntities
     * @var array
     */
    protected $_matchedEntities = array(
        TBT_Rewards_Model_Sales_Order::ENTITY => array(self::EVENT_TYPE_ORDER_PLACE_AFTER)
    );

    /**
     * Class constructor
     * @see Varien_Object::_construct()
     */
    protected function _construct()
    {
        $this->_init('tbtreports/indexer_order');
    }

    /**
     * Retrieve Indexer name
     * @return string
     */
    public function getName()
    {
        return Mage::helper('tbtreports')->__('MageRewards Reports: Order Data');
    }

    /**
     * Retrieve Indexer description
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('tbtreports')->__('Used to generate order related reports in MageRewards');
    }

    /**
     * match whether the reindexing should be fired
     * @param Mage_Index_Model_Event $event
     * @return bool
     */
    public function matchEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();
        if (isset($data[self::EVENT_MATCH_RESULT_KEY])) {
            return $data[self::EVENT_MATCH_RESULT_KEY];
        }

        $result = parent::matchEvent($event);
        if ($result) {
            $event->addNewData(self::EVENT_MATCH_RESULT_KEY, $result);
        }

        return $result;
    }

    /**
     * Register data required by process in event object
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        $dataObj = $event->getDataObject();
        if ($event->getType() == self::EVENT_TYPE_ORDER_PLACE_AFTER) {
            $order = $dataObj;
            $customer = $order->getCustomer();
            $event->addNewData('customer_id', $customer ? $customer->getId() : null);
            $event->addNewData('order_id', $order->getId());
        }

        $process = $event->getProcess();
        if ($process->getMode () == Mage_Index_Model_Process::MODE_MANUAL) {
            $process->changeStatus ( Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX );
        }
    }

    /**
     * Process event
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        return $this->callEventHandler($event);
    }
}