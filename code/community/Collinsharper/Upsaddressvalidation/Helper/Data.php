<?php
class Collinsharper_Upsaddressvalidation_Helper_Data extends Mage_Core_Helper_Abstract
{

    function getConfigData($x, $storeId = null)
    {
//        if (null === $storeId) {
//            $storeId = $this->getStore();
//        }

      //  return Mage::getStoreConfig('chaddress/validation/'. $x, $storeId);
        return Mage::getStoreConfig('customer/chaupsvalidation/'. $x);
    }

    function isFrontActive()
    {
        return $this->getConfigData('frontend_enabled');
    }

    function isBackendActive()
    {
        return $this->getConfigData('backend_enabled');
    }

    public function isAdmin()
    {
        if(Mage::app()->getStore()->isAdmin())
        {
            return true;
        }

        if(Mage::getDesign()->getArea() == 'adminhtml')
        {
            return true;
        }

        return false;
    }
}
	 