<?php 

/**
 * Adminhtml customer action tab
 *
 */
class  Collinsharper_Chcustomeruploads_Block_Adminhtml_Customer_Edit_Tab_Action
 extends Mage_Adminhtml_Block_Template 
  implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    public function __construct()
    {
	parent::__construct();
 	//$this->setId('order_chupload_action');	
        $this->setTemplate('chuploads/action.phtml');
     //   $this->setChFileUploads($this->getCustomerUploads());

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
    	if (Mage::registry('current_customer')->getId()) {
    		return true;
    	}
    	return false;
    }
    
    public function isHidden()
    {
	return false;
    	if (Mage::registry('current_customer')->getId()) {
    		return false;
    	}
    	return true;
    }
    
    public function getCustomerId()
    {
    	$customerId = $this->getCustomerInfo()->getEntityId();
    	return $customerId;
    }

    public function getFileUploads()
    {
        //  $this->setChFileUploads($this->getOrderUploads());
        return $this->getCustomerUploads();
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
    
    public function getOrderUploads()
    {
    	$collection = $this->getCollection();
        //TODO: these are backwords in query
        $collection->addFilter('order_id', $this->getOrderId());
        $data = array();
    	foreach($collection as $c)
    	{
            $data[] = $c->getData();
    	}
    	return $data;
    }

    public function getQuoteUploads()
    {
    	$collection = $this->getCollection();
        //TODO: these are backwords in query
    	$collection->addFilter('quote_id', $this->getOrderId());
        $data = array();
    	foreach($collection as $c)
    	{
            $data[] = $c->getData();
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
