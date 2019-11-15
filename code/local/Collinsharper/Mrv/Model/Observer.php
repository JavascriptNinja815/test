<?php
class Collinsharper_Mrv_Model_Observer
{
    const MY_RETRO_VIEWER_CUSTOMER_GROUP = 6;

    public function mrvLogin(Varien_Event_Observer $observer)
    {
        $customer = $observer->getCustomer();
        $this->setRetailCustomer($customer);
    }

	public function mrvRegister (Varien_Event_Observer $observer) {
        $customer = $observer->getCustomer();
        $this->setRetailCustomer($customer);
    }

    private function setRetailCustomer($customer) {
        $isRetailCustomer = Mage::getSingleton('core/session')->getRetailCustomer();
        if ($isRetailCustomer) {
            $customer->setData('group_id', $this::MY_RETRO_VIEWER_CUSTOMER_GROUP);
            $customer->save();
            return true;
        }else {
            return false;
        }
    }

}
