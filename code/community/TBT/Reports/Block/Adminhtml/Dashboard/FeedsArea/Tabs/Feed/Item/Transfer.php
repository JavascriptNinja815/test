<?php

class TBT_Reports_Block_Adminhtml_Dashboard_FeedsArea_Tabs_Feed_Item_Transfer
    extends Mage_Adminhtml_Block_Template
    implements TBT_Reports_Block_Adminhtml_Dashboard_FeedsArea_Tabs_Feed_Item_Interface
{
    /**
     * @var TBT_Reports_Model_Service_Transfer_Feed_Item
     */
    protected $_itemServiceModel;


    function _construct()
    {
        $this->_itemServiceModel = $this->getFeedItemServiceModel();
        return parent::_construct();
    }

    /**
     * @see TBT_Reports_Block_Adminhtml_Dashboard_FeedsArea_Tabs_Feed_Item_Interface::getId()
     * @return mixed
     * @throws Exception
     */
    public function getId()
    {
        return $this->_itemServiceModel->getTransfer()->getId();
    }

    /**
     * @see TBT_Reports_Block_Adminhtml_Dashboard_FeedsArea_Tabs_Feed_Item_Interface::setItemObject()
     * @param mixed $object
     * @return $this
     */
    public function setItemObject($object)
    {
        $this->_itemServiceModel->clearInstance();
        $this->_itemServiceModel->setTransfer($object);
        return $this;
    }

    /**
     * @see TBT_Reports_Block_Adminhtml_Dashboard_FeedsArea_Tabs_Feed_Item_Interface::getMessage()
     * @return string
     */
    public function getMessage()
    {
        return $this->_itemServiceModel->getTranslatedMessage();
    }

    /**
     * @see TBT_Reports_Block_Adminhtml_Dashboard_FeedsArea_Tabs_Feed_Item_Interface::getClasses()
     * @return array
     */
    public function getClasses()
    {
        return $this->_itemServiceModel->getClasses();
    }

    /**
     * Will return timestamp in ISO 8601 format
     * @see TBT_Reports_Block_Adminhtml_Dashboard_FeedsArea_Tabs_Feed_Item_Interface::getTimestamp()
     * @return string
     */
    public function getTimestamp()
    {
        $timestamp = $this->_itemServiceModel->getTransferTimestamp();
        return Mage::helper('rewards/datetime')->reformatDateString($timestamp, 'c');
    }

    /**
     * @return TBT_Reports_Model_Service_Transfer_Feed_Item
     */
    protected function getFeedItemServiceModel()
    {
        return Mage::getModel('tbtreports/service_transfer_feed_item');
    }
}