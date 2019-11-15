<?php
/**
 * 
 * @package Collinsharper_ChangeShippingMethod
 *
 * @author Maxim Nulman 
 */
class Collinsharper_ChangeShippingMethod_Block_Adminhtml_Shipping_Method extends Mage_Adminhtml_Block_Widget_Form_Container
{
    
    
    public function __construct()
    {
        
        $this->_controller = 'adminhtml_shipping_method';
        
        $order_id = $this->getRequest()->getParam('order_id');

        $this->_headerText = Mage::helper('sales')->__('Change Shipping Method');
        
        $this->_blockGroup = 'chcanpost2module';
        
        parent::__construct();
        
    }
    
    
}
