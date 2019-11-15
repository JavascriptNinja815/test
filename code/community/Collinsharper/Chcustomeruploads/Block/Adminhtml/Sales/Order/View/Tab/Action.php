<?php

/**
 * Adminhtml customer action tab
 *
 */
class  Collinsharper_Chcustomeruploads_Block_Adminhtml_Sales_Order_View_Tab_Action
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    var $_order_id = null;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('chuploads/action.phtml');
    }

    //down here are the mandatory methods you have to include
    public function getTabLabel()
    {
        return Mage::helper('collinsharper_chcustomeruploads')->__('Customer Uploads / FTP');
    }

    public function getTabTitle()
    {
        return Mage::helper('collinsharper_chcustomeruploads')->__('Customer Uploads / FTP');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    public function getCustomerId()
    {
        return 0;
    }

    public function getCustomerUploads()
    {
        $collection = $this->getCollection();
        $collection->addFilter('customer_id', $this->getCustomerId());
        $data = array();
        foreach($collection as $c)
        {
            $data[] = $c;
        }
        return $data;
    }

    public function getFileUploads()
    {
        //  $this->setChFileUploads($this->getOrderUploads());
        return $this->getOrderUploads();
    }

    public function setOrderId($x)
    {
        $this->_order_id = $x;
        return $this;
    }

    public function getOrderId()
    {
        if( $this->_order_id  ) { return  $this->_order_id ; }
        if(Mage::registry('chinquiry_data') && Mage::registry('chinquiry_data')->getIncrementId() ) {
            return Mage::registry('chinquiry_data')->getIncrementId();
        }

        return  (Mage::registry('current_order') ? Mage::registry('current_order')->getIncrementId() : false);
    }


    public function getOrderUploads()
    {

        $data = array();
        mage::log(__METHOD__ . " and order " . $this->getOrderId());
        if(!$this->getOrderId()) {
            return $data;
        }

        $collection = $this->getCollection();
        //TODO: these are backwords in query
        //$collection->addFilter('order_ids', $this->getOrderId());
        //$collection->getSelect()->where("{$this->getOrderId()} in (main_table.order_ids)");
        //  $collection->addFilter('order_id', $this->getOrder()->getIncrementId());

        $orderId = $this->getOrderId();
//        $orderId = $this->getOrderIncrementId();

        mage::log(__METHOD__ . __LINE__ . " we have " . $orderId);

        $collection->getSelect()->where("'{$orderId}' in (main_table.order_ids)");

        mage::log(__METHOD__ . __LINE__ . "{ sel " .  $collection->getSelect()->__toString());
        foreach($collection as $c) {
            $data[] = $c;
        }
        return $data;
    }

    public function getQuoteUploads()
    {
        $data = array();
        if(!$this->getQuoteId()) {
            return $data;
        }

        $collection = $this->getCollection();
        //TODO: these are backwords in query
        $collection->getSelect()->where("{$this->getQuoteId()} in (main_table.quote_ids)");
        foreach($collection as $c)
        {
            $data[] = $c;
        }
        return $data;
    }

    public function getCollection()
    {
        $collection = Mage::getModel('collinsharper_chcustomeruploads/upload')->getCollection();
        //  $collection = new Collinsharper_Chcustomeruploads_Model_Resource_Upload_Collection;
        $collection
            ->addFieldToSelect('*')
            //   ->addFieldToFilter('customer_id', $this->getCustomerId())
            // ->addFieldToFilter('status', '1')
        ;
        return $collection;
    }

    public function getCustomerInfo()
    {
        return Mage::registry('current_customer');
    }



}
