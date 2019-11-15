<?php
/**
 * 
 * @package Collinsharper_ChangeShippingMethod
 *
 * @author Maxim Nulman 
 */
class Collinsharper_ChangeShippingMethod_Block_Adminhtml_Sales_Order_Create_Shipping_Method_Form extends Mage_Adminhtml_Block_Sales_Order_Create_Shipping_Method_Form
{
    
    
    public function __construct()
    {
        
        parent::__construct();
        
    }
    
    
    /**
     * Retrieve quote model object
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
     
        $order_id = $this->getRequest()->getParam('order_id', 0);
        
        $quote = false;
        
        if (!empty($order_id)) {
       
            $order = Mage::getModel('sales/order')->load($order_id);          

            $quote_id = $order->getQuoteId();

            $quote = Mage::getModel('sales/quote')->getCollection()->addFieldToFilter('entity_id', $quote_id)->getFirstItem();

        }
        
        return $quote;
        
    }
    
    
    public function getOrder() {
        
        $order_id = $this->getRequest()->getParam('order_id', 0);
        
        $order = false;
        
        if (!empty($order_id)) {
       
            $order = Mage::getModel('sales/order')->load($order_id);          

        }
        
        return $order;
        
    }
    
    
    public function isMethodActive($code) {
        
        return ($code == $this->getOrder()->getShippingMethod());
        
    }
    
    
}
