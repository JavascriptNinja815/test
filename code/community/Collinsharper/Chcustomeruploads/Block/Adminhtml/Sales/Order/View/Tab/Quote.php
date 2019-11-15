<?php 

/**
 * Adminhtml customer action tab
 *
 */
class  Collinsharper_Chcustomeruploads_Block_Adminhtml_Sales_Order_View_Tab_Quote
 extends Mage_Adminhtml_Block_Template 
  implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

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
       return $this->getQuoteUploads();
    }

    public function getOrderId()
    {
        return (int)(Mage::registry('current_order') ? Mage::registry('current_order')->getId() : false);
    }


    public function getOrderUploads()
    {

        $data = array();
        if(!$this->getOrderId()) {
            return $data;
        }

        $collection = $this->getCollection();
        //TODO: these are backwords in query
        //$collection->addFilter('order_ids', $this->getOrderId());
        $collection->getSelect()->where("{$this->getOrderId()} in (main_table.order_ids)");

        mage::log(__METHOD__ . __LINE__ . "{ sel " .  $collection->getSelect()->__toString());
        foreach($collection as $c) {
            $data[] = $c;
    	}
    	return $data;
    }

    public function getQuoteUploads()
    {
        mage::log(__METHOD__ . __LINE__ );
        $data = array();
       $id = $this->getRequest()->getParam('id');
        mage::log(__METHOD__ . __LINE__ . " and ID " . $id);


        if(!$id) {
            return $data;
        }
        $collection = $this->getCollection();
        $collection->getSelect()->where("{$id} in (main_table.quote_ids)");
    	foreach($collection as $c) {
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
