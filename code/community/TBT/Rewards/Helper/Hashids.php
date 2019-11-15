<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the SWEET TOOTH POINTS AND REWARDS
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
 *
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2016 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper for crypting/decrypting hash ids
 *
 * @category   TBT
 * @package    TBT_Rewards
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Helper_Hashids
{
    /**
     * Crypts a code or array of codes using hashids lib
     * Note: making sure that result type matches the input type (string|array)
     *
     * @see SweetTooth_Hashids_Hashids
     * @param string|array $ids
     * @return string|array
     */
    public function cryptIds($ids)
    {
        $securityKey = (string)Mage::getConfig()->getNode('global/crypt/key');

        $hashIds = new SweetTooth_Hashids_Hashids($securityKey);

        $encodedIds = $hashIds->encode($ids);

        if (is_array($ids)) {
            return $encodedIds;
        }

        if (is_array($encodedIds)) {
            return array_pop($encodedIds);
        }

        return $encodedIds;
    }

    /**
     * Decrypts a code or array of codes using hashids lib
     * Note: decode method always returns an array even if string provided
     *
     * @see SweetTooth_Hashids_Hashids
     * @param string|array $ids
     * @return string|array
     */
    public function decryptIds($ids)
    {
        $securityKey = (string)Mage::getConfig()->getNode('global/crypt/key');

        $hashIds = new SweetTooth_Hashids_Hashids($securityKey);

        $decodedIds = $hashIds->decode($ids);

        if (is_array($ids)) {
            return $decodedIds;
        }

        if (is_array($decodedIds)) {
            return array_pop($decodedIds);
        }

        return $decodedIds;
    }
}
