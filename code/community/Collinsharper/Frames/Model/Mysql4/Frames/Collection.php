<?php
/**
 * Collinsharper/Frames/Model/Mysql4/Frames/Collection.php
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
 * Collinsharper_Frames_Model_Mysql4_Frames_Collection
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
class Collinsharper_Frames_Model_Mysql4_Frames_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('chframes/frames');
    }
}