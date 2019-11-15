<?php
/** 
 *
 * @category    Collinsharper
 * @package     Collinsharper_ChangeShippingMethod
 * @author      Maxim Nulman
 */
class Collinsharper_ChangeShippingMethod_Adminhtml_SwitchController extends Mage_Adminhtml_Controller_Action
{
    
    
    public function indexAction()
    {
        
        $this->loadLayout();  
        
        $this->renderLayout();
        
    }
    
    
    public function getOptionsAction() {
        
        $quote_id = $this->getRequest()->getParam('quote_id', 0);
        
        $shipping_method = $this->getRequest()->getParam('method_code', '');                

        $quote = Mage::getModel('sales/quote')->getCollection()->addFieldToFilter('entity_id', $quote_id)->getFirstItem();
                
        $data = array();
        
        $data['options'] = array();
        
        if (preg_match('/^chcanpost2module_(.+)/', $shipping_method, $matches)) {

            $services = Mage::helper('chcanpost2module/rest_service')->getInfo($matches[1], $quote->getShippingAddress()->getCountry());

            $data['options'] = array();

            $available_options = array('SO', 'COV', 'HFP', 'DNS', 'LAD', 'D2PO');

            if (!empty($services->options->option)) {

                foreach ($services->options->option as $opt) {

                    $data['options'][] = (string)$opt->{'option-code'};

                }

            }

        }
        
        header('Content-type: application/json');
        
        echo json_encode($data);
        
        exit;
        
    }
    
    
    public function saveAction() {
                
        $this->loadLayout();  
        
        $quote_id = $this->getRequest()->getParam('quote_id', 0);
        
        $order_data = $this->getRequest()->getParam('order', 0);
        
        $description = $this->getRequest()->getParam('shipping_method_description', array());
        
        if (!empty($quote_id) && !empty($order_data['shipping_method'])) {
            
            $order = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('quote_id', $quote_id)->getFirstItem();

            $before_change_method = $order->getShippingDescription();
            
            if (!empty($description[$order_data['shipping_method']])) {
            
                $order->setShippingDescription($description[$order_data['shipping_method']]);
            
            }
            
            $order->setShippingMethod($order_data['shipping_method']);
            
            if (preg_match('/chcanpost2module_(.+)/', $order_data['shipping_method'], $matches)) {
            
                $total = $order->getGrandTotal();
                
                $max_coverage = Mage::helper('chcanpost2module/option')->getMaxCoverage($matches[1], $order->getShippingAddress()->getCountryCode());
                
                $coverage_amount = ($total < $max_coverage) ? $total : $max_coverage;
        
                $signature = $this->getRequest()->getParam('signature', 0);
                
                $coverage = $this->getRequest()->getParam('coverage', 0);
                
                $card_for_pickup = $this->getRequest()->getParam('card_for_pickup', 0);
                
                $do_not_save_drop = $this->getRequest()->getParam('do_not_save_drop', 0);
                
                $leave_at_door = $this->getRequest()->getParam('leave_at_door', 0);                
                
                $deliver_to_postoffice = $this->getRequest()->getParam('deliver_to_post_office', 0);
                
                $office_id = (!empty($deliver_to_postoffice)) ? $this->getRequest()->getParam('office', null) : null;
                
                Mage::getModel('chcanpost2module/quote_param')->updateForQuote(
                                       $quote_id, 
                                       (int)!empty($signature), 
                                       (int)!empty($coverage), 
                                       $coverage_amount, 
                                       (int)!empty($card_for_pickup), 
                                       (int)!empty($do_not_save_drop), 
                                       (int)!empty($leave_at_door),
                                       $office_id
                                      );
                
                $order->setState($order->getState(), $order->getStatus(), __('Shipping method has been changed from "%s" to "%s"', $before_change_method, $order->getShippingDescription()));
            
            } 
            
            $order->save();
            
            $quote = Mage::getModel('sales/quote')->getCollection()->addFieldToFilter('entity_id', $order->getQuoteId())->getFirstItem();
            
            $shipping_address = $quote->getShippingAddress();
            
            if (!empty($description[$order_data['shipping_method']])) {
            
                $shipping_address->setShippingDescription($description[$order_data['shipping_method']]);
            
            }
            
            $shipping_address->setShippingMethod($order_data['shipping_method']);
            
            $shipping_address->save();
            
            $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
            
        }
        
        $this->renderLayout();
        
    }
    
}