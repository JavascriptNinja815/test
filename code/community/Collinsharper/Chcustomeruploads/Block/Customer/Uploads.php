<?php

class Collinsharper_Chcustomeruploads_Block_Customer_Uploads extends Mage_Core_Block_Template
{

    protected $_orders;
    protected $_quotes;

    private function getCustomerId()
    {
        return Mage::getSingleton('customer/session')->getCustomerId();
    }

    public function getOrderList()
    {
        if(!$this->_orders) {
            //$orders = Mage::getModel('sales/order')->getCollection();
            $orders =  Mage::getModel('chinquiry/inquiry')->getCollection();
            $orders

                //->addAttributeToSelect('*')
                //->addAttributeToFilter('customer_id', $this->getCustomerId())
                ->addFieldToFilter('customer_id', $this->getCustomerId())
                //->addattributeToFilter('status', array('neq' => ))
            //    ->addAttributeToSort('created_at', 'DESC')

            ;
            $this->_orders = $orders;
        }


        return $this->_orders;
    }

    public function getQuoteList()
    {
        if(!$this->_quotes) {
            $quotes = Mage::getModel('qquoteadv/Qqadvcustomer')->getCollection();
            $quotes
                ->getSelect()->where('customer_id = ? ', $this->getCustomerId());
            $quotes
                //->addattributeToFilter('status', array('neq' => )) // (51,21,60)
                ->order('created_at', 'DESC');
            $this->_quotes = $quotes;
        }
        return $this->_quotes;
    }


    public function getCollection()
    {
        //$collection = Mage::getModel('collinsharper_chcustomeruploads/resource_upload_collection');
        $collection = Mage::getModel('collinsharper_chcustomeruploads/upload')->getCollection();
      //  $collection = new Collinsharper_Chcustomeruploads_Model_Resource_Upload_Collection;
        $collection
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', $this->getCustomerId())
            ->addFieldToFilter('status', '1');
        return $collection;
    }

    public function getThumbnailPath($item)
    {
        return mage::helper('collinsharper_chcustomeruploads')->getThumbnailPath($item->getEntityId());

    }

}
