<?php
/**
 * Collinsharper/Frames/Helper/Data.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Frames
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper Frames Extension Helper Class
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Frames
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Frames_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Generic Debug Log function
     *
     * @param string|null $_message Debug Message
     * @param int|null    $_level   Magento Log level
     *
     * @return  void
     */
    public function debugLog($_message, $_level = null)
    {
        Mage::log($_message, $_level, "ch_frames.log");
    }
}