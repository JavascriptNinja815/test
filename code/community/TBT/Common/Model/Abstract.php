<?php

abstract class TBT_Common_Model_Abstract extends Mage_Core_Model_Abstract
{
    abstract public function getModuleKey();
    
    protected function _beforeLoad($id, $field = null)
    {
        parent::_beforeLoad($id, $field);
        $this->_getLoyaltyHelper()->onModelBeforeLoad($this, $id, $field);
        return $this;
    }
    
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->_getLoyaltyHelper()->onModelAfterLoad($this);
        return $this;
    }
    
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $this->_getLoyaltyHelper()->onModelBeforeSave($this);
        return $this;
    }
    
    protected function _afterSave()
    {
        parent::_afterSave();
        $this->_getLoyaltyHelper()->onModelAfterSave($this);
        return $this;
    }
    
    public function afterCommitCallback()
    {
        parent::afterCommitCallback();
        $this->_getLoyaltyHelper()->onModelAfterCommitCallback($this);
        return $this;
    }
    
    protected function _beforeDelete()
    {
        parent::_beforeDelete();
        $this->_getLoyaltyHelper()->onModelBeforeDelete($this);
        return $this;
    }
    
    protected function _afterDelete()
    {
        parent::_afterDelete();
        $this->_getLoyaltyHelper()->onModelAfterDelete($this);
        return $this;
    }
    
    /**
     * Attempts to get the loyalty helper for this module.  Returns false if files cannot be found or validation fails.
     * @param bool $forwardToBillboard currently unused.  TODO: should we ever forward to billboard from a model?
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
                //$this->_forwardToBillboard('/billboard_noLicenseFiles', array(
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
