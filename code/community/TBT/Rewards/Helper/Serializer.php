<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 * https://www.sweettoothrewards.com/terms-of-service
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
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 * https://www.sweettoothrewards.com/terms-of-service
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
 * @copyright  Copyright (c) 2017 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Serializer helper
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
$dependency = Mage::getBaseDir('lib') . DS. 'SweetTooth' . DS . 'Unserialize' . DS . 'Parser.php';
if (file_exists($dependency) && is_readable($dependency)) {
    include_once($dependency);
} else {
    $message = Mage::helper('rewards')->__("Wasn't able to load some dependencies.");
    Mage::getSingleton('core/session')->addError($message);
    Mage::helper('rewards/debug')->log($message);
    return $this;
}

/**
 * Helper responsible for serializing data. Security issues were found and fixed by the Magento 
 * team in the SUPEE-6788 patch, namely issue number APPSEC-1079 (details can be found here: 
 * https://magento.com/security/patches/supee-6788).
 * 
 * These issues made it possible for remote code to be executed and for information to be leaked in some 
 * cases when data was unserialized. Sweet Tooth complies with the changes made by magento and uses
 * the same unserialization parser to keep up with Magento's security standards.
 */
class TBT_Rewards_Helper_Serializer extends Mage_Core_Helper_Abstract
{
    /**
     * Serialize data
     * 
     * @param mixed $data
     * @return string
     */
    public function serializeData($data)
    {
        return serialize($data);
    }
    
    /**
     * Unserialize data (using the library provided by Magento in recent versions)
     * 
     * @param string $str
     * @return array
     * 
     * @throws Exception
     */
    public function unserializeData($str)
    {
        try {
            $parser = new Magento_Unserialize_Parser();
            return $parser->unserialize($str);
        } catch (Exception $e) {
            return false;
        }
    }
}

