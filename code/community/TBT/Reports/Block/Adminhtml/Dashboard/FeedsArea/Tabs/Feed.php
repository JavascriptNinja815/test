<?php

/**
 * Class TBT_Reports_Block_Adminhtml_Dashboard_FeedsArea_Tabs_Feed
 *
 * @method $this setInitialTransfersCount(int $count)
 * @method int getInitialTransfersCount()
 */
class TBT_Reports_Block_Adminhtml_Dashboard_FeedsArea_Tabs_Feed extends Mage_Adminhtml_Block_Template
{
    protected $_items = null;

    /**
     * Will load the items collection
     * @param $count
     * @return array
     */
    public function getItems($count)
    {
        if (is_null($this->_items)) {
            $this->_items = array();

            $initialTransfersCount = $count;
            if ($initialTransfersCount) {
                $transferCollection = $this->getTransferFeedService()
                    ->getRecentTransfers($initialTransfersCount)
                    ->load();
                $transferItems = array_reverse($transferCollection->getItems());
                $this->_items = $transferItems;
            }

        }

        return $this->_items ;
    }

    /**
     * @return TBT_Reports_Model_Service_Transfer_Feed
     */
    protected function getTransferFeedService()
    {
        return Mage::getModel('tbtreports/service_transfer_feed');
    }
}