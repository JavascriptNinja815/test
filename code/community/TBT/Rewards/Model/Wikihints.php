<?php

/**
 * Sweet Tooth
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS 
 * License, which extends the Open Software License (OSL 3.0).

 * The Open Software License is available at this URL: 
 * http://opensource.org/licenses/osl-3.0.php
 * 
 * DISCLAIMER
 * 
 * By adding to, editing, or in any way modifying this code, Sweet Tooth is 
 * not held liable for any inconsistencies or abnormalities in the 
 * behaviour of this code. 
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Sweet Tooth, outlined in the 
 * provided Sweet Tooth License. 
 * Upon discovery of modified code in the process of support, the Licensee 
 * is still held accountable for any and all billable time Sweet Tooth spent 
 * during the support process.
 * Sweet Tooth does not guarantee compatibility with any other framework extension. 
 * Sweet Tooth is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to 
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy 
 * immediately.
 * 
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Special Header
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_WikiHints extends Mage_Core_Model_Abstract 
{
    /* Adds a wikiHint link to the specified element if wikiHints is enabled.
     * 
     * This function will call the protected function generateLink(..) to produce 
     * the HTML code neccesary for the wikiHint link. Depending on which element
     * the link is for (either a fieldset or a form element), the HTML code generated will be placed appropriatly so that
     * it is displayed to the right of that element on the screen. 
     * 
     * Usage: Mage::getSingleton('rewards/wikihints')->addWikiHint($fieldset/$formElement, $urlPath, $pageTitle);
     * Example: Mage::getSingleton('rewards/wikihints')->addWikiHint($fieldset, "article/installation-guide", "Installation Guide");
     * 
     * 
     * @param Varien_Data_Form_Element_Fieldset $element or Varien_Data_Form_Element_Abstract $element, the item which the wikiHint link will be associated with. 
     * @param string $urlPath, the URL path to the KB article - without the domain
     * @param string $pageTitle, the title of the article page on the wiki website.
     *  
     * @return $element, the form element or fieldset which was passed in.
     * 
     */
    public function addWikiHint($element, $urlPath, $pageTitle)
    {
        /* Should we allow disabling wikiHints?		 
            $enabled = Mage::getStoreConfig ( 'rewards/WikiHints/is_enabled' );
            if (!$enabled) return $element;
        */

        if ($element instanceof Varien_Data_Form_Element_Fieldset) {
            $linkProperties = $this->generateLinkProperties($element->getId(), $urlPath, $pageTitle);
            $element->setWikiHintLink($linkProperties);

            if ($element->getRenderer() && strpos($element->getRenderer()->getTemplate(), 'widget/form/renderer/fieldset.phtml') !== false) {
                $element->getRenderer()->setTemplate('rewards/widget/form/renderer/fieldset.phtml');
            }
        } else if ($element instanceof Varien_Data_Form_Element_Abstract) {
            $linkHtml = $this->generateLink($element->getId(), $urlPath, $pageTitle);
            $afterElementHtml = $element->getAfterElementHtml();
            $linkHtml = empty($afterElementHtml) ? $linkHtml : $linkHtml.$afterElementHtml;
            $element->setAfterElementHtml($linkHtml);
            $element->setFieldsetHtmlClass('rewards-wikihinted');
        }

        return $element;	
    }

    /**
     * Get WikiHint Link Object
     * 
     * @param string $elementId
     * @param string $urlPath
     * @param string $pageTitle
     * @return \Varien_Object
     */
    protected function generateLinkProperties($elementId, $urlPath, $pageTitle)
    {
        $baseWikiURL = 'http://support.magerewards.com/';

        $url = $baseWikiURL . $urlPath;
        $pageTitle = trim(str_replace(" ", "-", $pageTitle));
        $linkId = $elementId . "_wikiHint";

        $linkProperties = array(
            'id' => Mage::helper('core')->escapeHtml($linkId),
            'url' => Mage::helper('core')->escapeHtml($url),
            'title' => Mage::helper('core')->escapeHtml($pageTitle),
        );

        $linkObj = new Varien_Object($linkProperties);

        return $linkObj;
    }

    protected function generateLink($elementId, $urlPath, $pageTitle)
    {
        $baseWikiURL = 'http://support.magerewards.com/';

        $url = $baseWikiURL . $urlPath;
        $pageTitle = trim(str_replace(" ", "-", $pageTitle));
        $linkId = $elementId . "_wikiHint";

        // images/fam_help.gif
        return "<a id = \"$linkId\" class=\"wikiHint\"	href=\"$url\" title=\"$pageTitle\" target=\"_blank\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>";	
    }

    public function getBaseWikiURL() 
    {
        $baseWikiURL = Mage::getStoreConfig ( 'rewards/WikiHints/baseURL' );

        //@nelkaake: If the page is supposed to be HTTPS and the AJAX call is not HTTPS, add HTTPS
        // if it's HTTP and the url returned HTTPS, remove HTTPS
        if(  isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && strpos(strtolower($baseWikiURL), 'https') !== 0) {
            $baseWikiURL = str_replace('http', 'https', $baseWikiURL);
        } elseif(!isset($_SERVER['HTTPS']) || !$_SERVER['HTTPS'] && strpos(strtolower($baseWikiURL), 'https') === 0) {
            $baseWikiURL = str_replace('https', 'http', $baseWikiURL);
        } else {
            // the url is fine and we can continue because it's using the correct encryption
        }

        return $baseWikiURL;
    }
}
