<?php
/**
 * Collinsharper/Reels/Model/Mysql4/Reels/Collection.php
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chshopby
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
/**
 * Collinsharper_Reels_Model_Mysql4_Reels_Collection
 *
 * PHP version 5
 *
 * @category  Collinsharper
 * @package   Collinsharper_Chshopby
 * @author    KL <support@collinsharper.com>
 * @copyright 2014 Collinsharper Inc.
 * @link      http://www.collinsharper.com/
 *
 */
class Collinsharper_Reels_Model_Mysql4_Reels_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('chreels/reels');
    }
}