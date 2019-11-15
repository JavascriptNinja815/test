<?php

class TBT_Common_Block_Billboard_NoLicense extends TBT_Billboard_Block_Billboard
{
    protected function _beforeToHtml()
    {
        $title = $this->hasData('title') ? $this->getData('title') : "Oops! Please specify your license key.";
        $displayContinueLink = $this->hasData('displayContinueLink') ? $this->getData('displayContinueLink') : true;
        $this->setTitle($title)
            ->setDisplayContinueLink(false);
        $moduleName = $this->hasModuleName() ? $this->getModuleName() : 'Module';
        $configUrl  = $this->hasConfigUrl()  ? $this->getConfigUrl()  : $this->getUrl('adminhtml/system_config/edit');
        
        parent::_beforeToHtml();
        
        $block = $this->getLayout()->createBlock('tbtbillboard/billboard_section')
            ->setData('heading', "Step 1")
            ->setData('content', "Go to {$moduleName} <a href='{$configUrl}' target='_window'>configuration</a>.");
        $this->_sections[] = $block;
        
        $block = $this->getLayout()->createBlock('tbtbillboard/billboard_section')
            ->setData('heading', "Step 2")
            ->setData('content', "Enter license key from purchasing email.");
        $this->_sections[] = $block;
        
        if ($displayContinueLink) {
            $this->_sections[] = $this->getLayout()->createBlock('tbtbillboard/billboard_section_continuelink');
        }
        
        return $this;
    }
}
