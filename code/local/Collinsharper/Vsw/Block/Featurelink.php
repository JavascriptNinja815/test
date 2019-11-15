<?php
class Collinsharper_Vsw_Block_Featurelink extends Mage_Core_Block_Template
{
    /**
     * Render Feature Link tracking scripts
     *
     * @return string
     */
    protected function _toHtml()
    {

	if(!strstr(mage::helper('core/url')->getCurrentUrl(), 'checkout/onepage/success')) {
		return '';
	}

        return parent::_toHtml();
    }
}
