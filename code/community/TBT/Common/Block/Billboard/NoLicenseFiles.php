<?php

class TBT_Common_Block_Billboard_NoLicenseFiles extends TBT_Billboard_Block_Billboard
{
    protected function _beforeToHtml()
    {
        $title = $this->hasData('title') ? $this->getData('title') : "Uh oh!  Important license files are missing!";
        $displayContinueLink = $this->hasData('displayContinueLink') ? $this->getData('displayContinueLink') : false;
        $this->setTitle($title)
            ->setDisplayContinueLink(false);
        $moduleKey = $this->hasModuleKey() ? $this->getModuleKey() : 'unspecifiedModule';
        
        parent::_beforeToHtml();
        
        $block = $this->getLayout()->createBlock('tbtbillboard/billboard_section')
            ->setData('content', "The license files for the '{$moduleKey}' module cannot be found. Please contact MageRewards for more information at support@magerewards.com.");
        $this->_sections[] = $block;
        
        if ($displayContinueLink) {
            $this->_sections[] = $this->getLayout()->createBlock('tbtbillboard/billboard_section_continuelink');
        }
        
        return $this;
    }
}
