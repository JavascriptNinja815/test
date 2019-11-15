<?php

abstract class TBT_Common_Front_AbstractController extends Mage_Core_Controller_Front_Action
{
    abstract public function getModuleKey();
    
    public function preDispatch()
    {
        parent::preDispatch();
        
        $moduleName = $this->getModuleKey();
        $license = Mage::helper('tbtcommon')->getLoyaltyHelper($moduleName);
        
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            if (!$license || !$license->isValid()) {
                // TODO: include a generic no_license billboard here
                $this->getResponse()->setBody("Please verify the " . $license->getModuleName() . " module license is correct in the <a href='{$this->getUrl('adminhtml/system_config')}'>" .
                    "Magento Configuration</a>, or contact MageRewards for help at support@magerewards.com.");
            }
            
            // Notify loyalty checker about module activity for recurring events
            // TODO: Have this ping global TBT metrics at an interval
            $license->onModuleActivity();
        }
        
        return $this;
    }
    
    /** This method is used to redirect to the Billboard which is used to display
     * backend errors/notices in an elegant way.  Forwards to a pre-defined billboard structure.
     * 
     * e.g. _forwardToBillboard('rewardsinstore/billboard_nolicense');
     * 
     * @return boolean Whether or not the Billboard module is available
     */
    protected function _forwardToBillboard($blockKey)
    {
        if (!Mage::getConfig()->getModuleConfig('TBT_Billboard')->is('active', 'true') ||
            Mage::getStoreConfig('advanced/modules_disable_output/TBT_Billboard')) {
            return false;
        }
        
        $this->_forward('show', 'billboard', '', array('billboardKey' => $blockKey));
        return true;
    }
    
    /**
     * This function does not exist previous to Magento 1.4.0.0
     */
    protected function _title($text = null, $resetIfExists = true)
    {
        if (Mage::helper('tbtcommon/version')->isBaseMageVersionAtLeast('1.4.0.0')) {
            return parent::_title($text, $resetIfExists);
        }
        return $this;
    }
    
    /**
     * Helper for rewardsintore specific logging
     */
    protected function log($msg, $level = null) 
    {
        Mage::helper('tbtcommon')->log($msg, $this->getModuleKey(), $level);
    }
}
