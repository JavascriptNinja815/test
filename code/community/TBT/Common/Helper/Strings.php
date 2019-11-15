<?php
/**
 * This helper class is used for help with manipulating strings.
 * 
 */
class TBT_Common_Helper_Strings extends Mage_Core_Helper_Abstract
{

    /**
     * Replaces the contents of a string with an HTML anchor link.
     * 
     * @param string $text Example: "Go to the Sweet Tooth Points.com [config_link]configuration section[/config_link]."
     * @param string $link_key for this example it would be 'config_link'
     * @param string $url url to the place we'd want to the text between [config_link] and [/config_link] to link to.
     * @param array [$options = array()] other options ( use array('target') => '_abcd') to set the link target)
     */
    public function getTextWithLinks($text, $link_key, $url, $options = array()) {
        if ( isset( $options['target'] ) ) {
            $a_start = "<a href='{$url}' target='{$options['target']}'>";
        } else {
            $a_start = "<a href='{$url}'>";
        }
        $text = str_replace( "[{$link_key}]", $a_start, $text );
        $text = str_replace( "[/{$link_key}]", "</a>", $text );
        return $text;
    }
}
