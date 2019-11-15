<?php
/**
 * Collinsharper/Chusps/Helper/Data.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chusps
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @license   http://opensource.org/licenses/gpl-license.php  GNU General Public License (GPL)
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper USPS Extension Helper Class
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chusps
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @license   http://opensource.org/licenses/gpl-license.php  GNU General Public License (GPL)
 * @link      http://www.collinsharper.com/
 *
 */

class Collinsharper_Chusps_Helper_Data extends Mage_Core_Helper_Abstract
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
        Mage::log($_message, $_level, "ch_usps.log");
    }
}
