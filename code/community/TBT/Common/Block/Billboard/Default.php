<?php

class TBT_Common_Block_Billboard_Default extends TBT_Billboard_Block_Billboard
{
    protected function _beforeToHtml()
    {
        $title = $this->hasData('title') ? $this->getData('title') : "Default Billboard";
        $displayContinueLink = $this->hasData('displayContinueLink') ? $this->getData('displayContinueLink') : false;
        $this->setTitle($title)
            ->setDisplayContinueLink(false);
        
        parent::_beforeToHtml();
        
        $block = $this->getLayout()->createBlock('tbtbillboard/billboard_section')
            ->setData('content', "This is a thoroughly default billboard.");
        $this->_sections[] = $block;
        
        if ($displayContinueLink) {
            $this->_sections[] = $this->getLayout()->createBlock('tbtbillboard/billboard_section_continuelink');
        }
        
        return $this;
    }
}
