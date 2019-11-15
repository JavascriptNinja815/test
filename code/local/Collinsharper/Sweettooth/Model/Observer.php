<?php

class Collinsharper_Sweettooth_Model_Observer 
{
    
    private $event_data = array();

    public function sweetQ()
    {

        Mage::log('sweet Q start');

        $collection = Mage::getModel('chsweettooth/sweetEvent')->getCollection();

        $collection->addFieldToFilter('event_status', 'new');
        
        foreach ($collection as $event) {

            Mage::log(print_r($event->getData(), 1));
            
            if ($event->getData('event_type') == 'new customer') {

                $customer = Mage::getModel('customer/customer')->load($event->getData('resource_id'));

                $event->setEventStatus($this->newCustomerApi($customer) ? 'accepted' : 'failed');

                $event->save();

            }

            if ($event->getData('event_type') == 'new order') {

                $order = Mage::getModel('sales/order')->load($event->getData('resource_id'));

                if (in_array(
                         $order->getStatus(), 
                         explode(',', Mage::getStoreConfig('chsweettooth/chsweettooth_api_conf/order_status'))
                    )) {
                 
                    Mage::log("Sending API request");
                    
                    $event->setEventStatus($this->newOrderApi($order) ? 'accepted' : 'failed');

                    $event->setEventData(serialize($this->getEventData()));
                    
                    $event->save();
                 
                } else {
                    
                    Mage::log("Skip API request for status = " . $order->getStatus());
                    
                }

            }

        }

    }

    /**
     * Check if order status or shipped amount have been changed 
     * @param type $observer
     */
    public function updateOrder($observer)
    {
        
        $order = $observer->getEvent()->getOrder();
        
        if (in_array(
                 $order->getStatus(), 
                 explode(',', Mage::getStoreConfig('chsweettooth/chsweettooth_api_conf/order_status'))
            )) {
        
            Mage::log($order->getId().': '.$order->getStatus());

            $collection = Mage::getModel('chsweettooth/sweetEvent')->getCollection();

            $collection->addFieldToFilter('resource_id', $order->getId());

            $collection->addFieldToFilter('event_type', 'new order');

            $event = $collection->getFirstItem();

            Mage::log(print_r($event->getData(), 1));
            
            $event_data = $event->getEventData();
            
            if (!empty($event_data)) {
                
                $event_data = unserialize($event_data);
                
                Mage::log(print_r($event_data, 1));
                
                Mage::log('order shipping amount = '.$this->getShippedItemsAmount($order));
                
                if ($event_data['rewardable_total'] != $this->getShippedItemsAmount($order)) {
                    
                    Mage::log("set status to new");
                    
                    $event->setEventStatus('new');
                    
                    $event->save();
                    
                }
                
            }                   
        
        }
        
    }
    
    public function newCustomer($observer) {       
        
        $data = array(
            'event_type' => 'new customer',
            'resource_id' => $observer->getEvent()->getCustomer()->getId(),
            'event_status' => 'new',
        );

        $model = Mage::getModel('chsweettooth/sweetEvent');

        $model->setData($data);

        $model->save();

    }

    public function newCustomerApi($customer)
    {
   
	Mage::log("new customer event");

	Mage::log(print_r($customer->getData(), 1));

        $event_data = array(
            'external_id' => $customer->getId(),
            'first_name' => $customer->getFirstname(),
            'last_name' => $customer->getLastname(),
            'email' => $customer->getEmail(),
            'external_created_at' => date('c'),
            'external_updated_at' => date('c') 
        );
        
        $data = array(
            'event' => array(
                 'topic' => 'customer/updated',
                 'data' => $event_data
            )
        );

        $this->setEventData($event_data);
        
        try {
             
             $status = $this->apiCall($data);

             Mage::log('Sweettooth API response status: '. $status);
        
        } catch (Exception $e) {

	     Mage::log('Sweettooth API call error: '.$e->getMessage());

        }

        return (!empty($status) && in_array($status, array(202, 200)));

    }

    
    public function newOrder($observer)
    {

        $data = array(
            'event_type' => 'new order',
            'resource_id' => $observer->getEvent()->getOrder()->getId(),
            'event_status' => 'new',
        );

        $model = Mage::getModel('chsweettooth/sweetEvent');

        $model->setData($data);

        $model->save();

    }

    public function newOrderApi($order)
    {

        Mage::log('new order');

//        Mage::log(print_r($order->getData(), 1));

        $event_data = array(
            'external_id' => $order->getId(),
            'subtotal' => $order->getSubtotal(),
            'grand_total' => $order->getGrandTotal(),
            'rewardable_total' => $this->getShippedItemsAmount($order),
            'external_created_at' => date('c'),
            'external_updated_at' => date('c'),
            'payment_status' => 'paid',
            'coupons' => array(),
            'customer' => array(
                'external_id' => ($order->getData('customer_is_guest') ? time() : $order->getData('customer_id')),
                'first_name' => $order->getData('customer_firstname'),
                'last_name' => $order->getData('customer_lastname'),
                'email' => $order->getData('customer_email'),
                'external_created_at' => date('c'),
                'external_updated_at' => date('c')
            ) 
        );
        
        $data = array(
           'event' => array(
                'topic' => 'order/updated',
                'data' => $event_data
           )
        );

        Mage::log("Order update request: ".print_r($data, 1));
        
        $this->setEventData($event_data);
        
        try {

            $status = $this->apiCall($data);

            Mage::log('Sweettooth API response status: '. $status);

        } catch (Exception $e) {

            Mage::log('Sweettooth API call error: '.$e->getMessage());

        }

        return (!empty($status) && in_array($status, array(202, 200)));
         
    }


    private function apiCall($data)
    {

        $json = json_encode($data);
        
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.sweettooth.io/v1/events');

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); 

        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',                                                                                
            'Accept: application/json',
            'Content-Length: ' . strlen($json),
            'Authorization: Bearer '.Mage::getStoreConfig('chsweettooth/chsweettooth_api_conf/api_key')
        ));         

        $result = curl_exec($ch);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        return $status;

    }

    private function setEventData($data)
    {
        $this->event_data = $data;
    }
    
    private function getEventData()
    {
        return $this->event_data;
    }
    
    private function getShippedItemsAmount($order)
    {
        
        $amount = 0;
        
        foreach($order->getShipmentsCollection() as $shipment) {
            
            $itemsCollection = $shipment->getItemsCollection();
            
            foreach ($itemsCollection as $item) {
                
                $amount += $item->getPrice()*$item->getQty();
                
            }
            
        }
        
        return $amount;
        
    }
    
}
