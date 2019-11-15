<?php

/**
 * Grid column renderer for reel platform
 *
 * @author Rudie Wang <rwang@collinsharper.com>
 */
class Collinsharper_Reels_Block_Adminhtml_Reels_Grid_Renderer_Platform extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    /**
     * Extracts platform from the platform data
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $platform = $row->getPlatform();

        if (is_null($platform) || $platform == '') {
            $platform = 'Web';
        }
        $row->setPlatform($platform);
        return parent::render($row);
    }
}
