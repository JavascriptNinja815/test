<?php

class Collinsharper_Reels_Block_Adminhtml_Renderer_Image extends Varien_Data_Form_Element_Abstract
{

    protected $_element;

    public function getElementHtml() {
        $html = '';

        if ( $this->_getUrl()) {
            $url = $this->_getUrl();
            if( !preg_match("/^http\:\/\/|https\:\/\//", $url) ) {
                $url = Mage::getBaseUrl('media') . $url;
            }
            //onclick="imagePreview(\''.$this->getHtmlId().'_image\'); return false;"
            //TODO fixc this ensure we have thumb.. etc href shoudl goto full?
            $html = '<a target="_blank" href="'.$url.'" ><img src="'.$url.'" id="'.$this->getHtmlId().'_image" title="'.$this->getValue().'" alt="'.$this->getValue().'" height="22" width="22" /></a> ';
        }
       // $this->setClass('input-file');
     //   $html.= parent::getElementHtml();
       // $html.= $this->_getDeleteCheckbox();
       // mage::log(__METHOD__ . __LINE__ ." we have ht5ml " . $html);
        return $html;
    }

    protected function _getUrl() {
        // TODO broke?!?
        return Mage::registry('reels_data')->getData('thumb');
        return $this->getValue();
    }

    public function getName() {
        return  $this->getData('name');
    }
}