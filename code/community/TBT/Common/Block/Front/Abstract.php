<?php

abstract class TBT_Common_Block_Front_Abstract extends Mage_Core_Block_Template
{
    abstract public function getModuleKey();
    
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();
        $this->_getLoyaltyHelper()->onBlockBeforeToHtml($this);
        return $this;
    }
    
    protected function _afterToHtml($html)
    {
        parent::_afterToHtml($html);
        $this->_getLoyaltyHelper()->onBlockAfterToHtml($this, $html);
        return $html;
    }
    
    protected function _beforeChildToHtml($name, $child)
    {
        parent::_beforeChildToHtml($name, $child);
        $this->_getLoyaltyHelper()->onBlockBeforeChildToHtml($this, $name, $child);
        return $this;
    }
    
    /**
     * Attempts to get the loyalty helper for this module.  Returns false if files cannot be found or validation fails.
     * @param bool $forwardToBillboard currently unused.  TODO: should we ever forward to billboard from a block?
     * @return TBT_Common_Helper_LoyaltyAbstract|bool
     */
    protected function _getLoyaltyHelper($forwardToBillboard = true)
    {
        $moduleName = $this->getModuleKey();
        
        try {
            $license = Mage::helper('tbtcommon')->getLoyaltyHelper($moduleName);
        } catch (Exception $ex) {
            if ($forwardToBillboard) {
                // if the license module files can't be found, we can't continue
                //$this->_forwardToBillboard('tbtcommon/billboard_noLicenseFiles', array(
                //    'module_key' => $this->getModuleKey()
                //));
            }
            return false;
        }
        
        // TODO: leaving this just in case we still find it useful
        if (!$license->isValid()) {
            if ($forwardToBillboard) {
                // get the block key for the billboard to use if the license is invalid
                //$billboardKey = $license->getBillboard('noLicense');
                //$this->_forwardToBillboard($billboardKey, array(
                //    'module_name' => $license->getModuleName(),
                //    'config_url' => $this->getUrl("adminhtml/system_config/edit/section/{$this->getModuleKey()}")
                //    ));
            }
            return false;
        }
        
        return $license;
    }
    
    /**
     * Helper for rewardsintore specific logging
     */
    protected function log($msg, $level = null) 
    {
        Mage::helper('tbtcommon')->log($msg, $this->getModuleKey(), $level);
    }
}
