<?php
abstract class TBT_Common_Model_System_Config_Backend_LicenseAbstract extends Mage_Core_Model_Config_Data
{
    abstract public function getModuleKey();
    
    public function _afterSave() 
    {
        if (!$license = $this->getValue()) {
            throw new Exception("Please specify your license key.");
        }
        
        $this->_checkLicenseOverServer($license);
        return parent::_afterSave();
    }
    
    private function _checkLicenseOverServer($license) 
    {
        $response = Mage::helper('tbtcommon')->
            getLoyaltyHelper($this->getModuleKey())->
            fetchLicenseValidation($license);
            
        if($response['success'] && $response['data'] == 'license_valid') {
            Mage::getSingleton('core/session')->addSuccess($response['message']);
        } else {
            throw new Exception("License key is invalid. ({$response['message']})");
        }
        return $this;
    }
}
