<?php
/**
 * 
 * @package Collinsharper_ChangeShippingMethod
 *
 * @author Maxim Nulman 
 */
class Collinsharper_ChangeShippingMethod_Model_Observer
{

    
    public function setInfoTemplate($observer) {
        
        $controller   = $observer->getAction();

        $layout       = $controller->getLayout();
        
        $block = $layout->getBlock('order_tab_info');
        
        if(!$block) {

            return;
            
        }

        $block->setTemplate('chswitch/sales/order/view/tab/info.phtml');
        
    }
    
    
}
