<?php

class Image3D_CustomerExtend_Block_Account_Dashboard_Reels extends Mage_Core_Block_Template
{

    const XML_PATH_REEL_ONLY_TITLE = 'chreel_options/design/reel_only_title';
    const XML_PATH_VIEWER_SET_TITLE = 'chreel_options/design/viewer_set_title';
    const XML_PATH_IMPRINT_SET_TITLE = 'chreel_options/design/imprinted_set_title';

    public function getRhelper()
    {
        return Mage::helper('reels/reels');
    }
    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    public function getLegacyCompletedReels()
    {
        return 	$this->getRhelper()->loadReels(0, true, false, false);
    }

    public function getLegacyIncompleteReels()
    {
        return	$this->getRhelper()->loadReels(0, false, true, false);
    }
    public function hasLegacyReels()
    {
        return count($this->getLegacyIncompleteReels()) || count($this->getLegacyCompletedReels());
    }

    public function my_urlencode_dashboard($str) {
        return urlencode($str[1]);
    }

    /**
     * Returns configured label for Reel Only purchase option
     *
     * @return string
     */
    public function getReelOnlyTitle()
    {
        return Mage::getStoreConfig(self::XML_PATH_REEL_ONLY_TITLE);
    }

    /**
     * Returns configured label for Reel & Viewer Set purchase option
     *
     * @return string
     */
    public function getReelAndViewerSetTitle()
    {
        return Mage::getStoreConfig(self::XML_PATH_VIEWER_SET_TITLE);
    }

    /**
     * Returns configured label for Custom Imprinted Viewer & Reel Set purchase option
     *
     * @return string
     */
    public function getImprintedReelTitle()
    {
        return Mage::getStoreConfig(self::XML_PATH_IMPRINT_SET_TITLE);
    }

}
