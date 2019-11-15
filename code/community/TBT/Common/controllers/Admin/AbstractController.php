<?php

abstract class TBT_Common_Admin_AbstractController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('rewards');
    }
    
    abstract public function getModuleKey();
    
    public function preDispatch()
    {
        parent::preDispatch();
        
        $moduleName = $this->getModuleKey();
        
        try {
            $license = Mage::helper('tbtcommon')->getLoyaltyHelper($moduleName);
        } catch (Exception $ex) {
            // if the license module files can't be found, we can't continue
            $this->_forwardToBillboard('tbtcommon/billboard_noLicenseFiles', array(
                'module_key' => $this->getModuleKey()
            ));
            return $this;
        }
        
        // TODO: do we need this?
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            return $this;
        }
        
        // TODO: leaving this just in case we still find it useful
        if (!$license->isValid()) {
            // get the block key for the billboard to use if the license is invalid
            $billboardKey = $license->getBillboard('noLicense');
            $this->_forwardToBillboard($billboardKey, array(
                'module_name' => $license->getModuleName(),
                'config_url' => $this->getUrl("adminhtml/system_config/edit/section/{$this->getModuleKey()}")
            ));
            return $this;
        }
        
        $license->onAdminPreDispatch($this);
        
        // Notify loyalty checker about module activity for recurring events
        // TODO: Have this ping global TBT metrics at an interval
        $license->onModuleActivity();
        
        return $this;
    }
    
    public function postDispatch()
    {
        parent::postDispatch();
        
        $moduleName = $this->getModuleKey();
        
        try {
            $license = Mage::helper('tbtcommon')->getLoyaltyHelper($moduleName);
        } catch (Exception $ex) {
            // if the license module files can't be found, we can't continue
            $this->_forwardToBillboard('tbtcommon/billboard_noLicenseFiles', array(
                'module_key' => $this->getModuleKey()
            ));
            return $this;
        }
        
        $license->onAdminPostDispatch($this);
        
        return $this;
    }

    /** This method is used to redirect to the Billboard which is used to display
     * backend errors/notices in an elegant way.  Forwards to a pre-defined billboard structure.
     * 
     * @param string $blockKey The block key for the billboard to which to forward
     * @param array $data An array of parameters to pass into the billboard block
     * e.g. _forwardToBillboard('rewardsinstore/billboard_nolicense');
     * 
     * @return TBT_Common_Admin_AbstractController
     */
    public function forwardToBillboard($blockKey, $data = array()) {
        return $this->_forwardToBillboard($blockKey, $data);
    }
    
    /** This method is used to redirect to the Billboard which is used to display
     * backend errors/notices in an elegant way.  Forwards to a pre-defined billboard structure.
     * 
     * @param string $blockKey The block key for the billboard to which to forward
     * @param array $data An array of parameters to pass into the billboard block
     * e.g. _forwardToBillboard('rewardsinstore/billboard_nolicense');
     * 
     * @return TBT_Common_Admin_AbstractController
     */
    protected function _forwardToBillboard($blockKey, $data = array())
    {
        if (!Mage::getConfig()->getModuleConfig('TBT_Billboard')->is('active', 'true') ||
                Mage::getStoreConfig('advanced/modules_disable_output/TBT_Billboard')) {
            
            // if the billboard module can't be used, display a default message with the required params formatted in
            $defaultMessage = (string)Mage::getConfig()->getNode(str_replace('_', '/', $blockKey) . '/message');
            if (!$defaultMessage) {
                $defaultMessage = (string)Mage::getConfig()->getNode('tbtcommon/billboard/default/message');
            }
            array_unshift($data, $defaultMessage);
            
            // TODO: in the future it would be nice to display this more nicely
            $this->getResponse()->setBody(call_user_func_array(array($this, '__'), $data));
            return $this;
        }
        
        $data['billboardKey'] = $blockKey;
        $this->_forward('show', 'billboard', '', $data);
        return $this;
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
    
    public function redirect($path, $arguments = array())
    {
        return $this->_redirect($path, $arguments);
    }
    
    /**
     * Helper for rewardsintore specific logging
     */
    protected function log($msg, $level = null) 
    {
        Mage::helper('tbtcommon')->log($msg, $this->getModuleKey(), $level);
    }
}
