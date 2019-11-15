<?php

class TBT_RewardsLoyalty_Block_Billboard_NoAccount extends TBT_Billboard_Block_Billboard
{
    protected function _beforeToHtml() {
        $title = $this->hasData('title') ? $this->getData('title') : $this->__("Thanks for installing! Now you just need to connect your account.");
        $displayContinueLink = $this->hasData('displayContinueLink') ? $this->getData('displayContinueLink') : false;
        $this->setTitle($title)->setDisplayContinueLink($displayContinueLink);
        
        parent::_beforeToHtml();
        
        $this->_sections[] = $this->_getSection1Block();
        $this->_sections[] = $this->_getSection2Block();
        
        return $this;
    }
    
    /**
     * @return Mage_Core_Block_Abstract section 1
     */
    protected function _getSection1Block()
    {
        $body = <<<FEED
            <p>Already have a MageRewards account? Continue to your configuration to connect your account!</p>
            <p>&nbsp;</p>
			<p><button onclick="location.href='{$this->_getConfigSectionUrl()}';"><span><span><span>Connect My Account</span></span></span></button></p>
FEED;
        $body = Mage::helper('tbtcommon/strings')->getTextWithLinks($body, 'signup_link', $this->_getPlatformOverviewUrl(), array('target'=>'_window'));
        
        $block = $this->getLayout()
            ->createBlock('tbtbillboard/billboard_section')
            ->setHeading("Connect your Account")
            ->setContent($body)
			->addDivClass('connect-account');
        
        return $block;
    }
    
    protected function _getSection2Block()
    {
        $body = <<<FEED
            <p>Still need to sign up for a MageRewards account? Check out our plans to get started!</p>
			<p>&nbsp;</p>
			<p><button onclick="location.href='{$this->_getPlatformOverviewUrl()}';"><span><span><span>Sign Me Up</span></span></span></button></p>
FEED;
        $body = Mage::helper('tbtcommon/strings')->getTextWithLinks($body, 'config_url', $this->_getConfigSectionUrl(), array('target'=>'_window'));
        
        $block = $this->getLayout()
            ->createBlock('tbtbillboard/billboard_section')
            ->setHeading("Create a MageRewards Account")
            ->setContent($body)
			->addDivClass('connect-account');
        
        return $block;
    }
    
    /**
     * @return string
     */
    protected function _getConfigSectionUrl()
    {
        return $this->getUrl('adminhtml/system_config/edit', array('section' => 'rewards'));
    }
    
    /**
     * @return string
     */
    protected function _getPlatformOverviewUrl()
    {
        return "http://www.magerewards.com/pricing";
    }
}
