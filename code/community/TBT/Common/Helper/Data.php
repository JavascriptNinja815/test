<?php

class TBT_Common_Helper_Data extends Mage_Core_Helper_Abstract
{
    const LOG_EXTENSION = '.log';
    
    /**
     * This function returns the name of the license module associated
     * with the module name provided.
     * 
     * @param string $moduleName
     * @return string or null if no config found
     */
    public function getLoyaltyModule($moduleName)
    {
        $modulePath = 'tbtcommon/loyalty/' . $moduleName . '/module';
        $licenseModule = (string)Mage::getConfig()->getNode($modulePath);
        
        if (!$licenseModule) {
            return null;
        }
        
        return $licenseModule;
    }
    
    /**
     * This function returns the Loyalty Helper of the license
     * module associated with the module name provided.
     *
     * @param unknown_type $moduleName
     * @return TBT_Common_Helper_LoyaltyAbstract or null if no config found
     */
    public function getLoyaltyHelper($moduleName)
    {
        $licenseModule = $this->getLoyaltyModule($moduleName);
        if ($licenseModule === null) {
            throw new Exception("Module not configured properly");
        }
        
        $helperPath = 'tbtcommon/loyalty/' . $moduleName . '/helper';
        $licenseHelper = (string)Mage::getConfig()->getNode($helperPath);
        
        if (!$licenseHelper) {
            // Name of default helper
            $licenseHelper = 'loyalty';
        }
        
        return Mage::helper($licenseModule . '/' . $licenseHelper);
    }
    
    /**
     * Log to rewardsinstore file
     */
    public function log($msg, $module = null, $level = null)
    {
        $file = $module ? $module . self::LOG_EXTENSION : '';
        Mage::log($msg, $level, $file);
    }
}
