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
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * This class is used as a Source Model for admin system config fields
 * @package     TBT_Rewards
 * @subpackage  Model
 * @author      Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_System_Config_Source_ApproveOrderPointsOn
{
    /**
     * Don't Approce Automatically
     */
    const APPROVE_ORDER_POINTS_ON_DO_NOT = 0;

    /**
     * Approve on Invoice Creation
     */
    const APPROVE_ORDER_POINTS_ON_INVOICE = 1;

    /**
     * Approve on Shipment Creation
     */
    const APPROVE_ORDER_POINTS_ON_SHIPMENT = 2;

    /**
     * Approve on Order Complete
     */
    const APPROVE_ORDER_POINTS_ON_ORDER_COMPLETE = 3;
    
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::APPROVE_ORDER_POINTS_ON_DO_NOT,
                'label' => Mage::helper('rewards')->__("Don't Approve Automatically")
            ),
            array(
                'value' => self::APPROVE_ORDER_POINTS_ON_INVOICE,
                'label' => Mage::helper('rewards')->__("Invoice")
            ),
            array(
                'value' => self::APPROVE_ORDER_POINTS_ON_SHIPMENT,
                'label' => Mage::helper('rewards')->__("Shipment")
            ),
            array(
                'value' => self::APPROVE_ORDER_POINTS_ON_ORDER_COMPLETE,
                'label' => Mage::helper('rewards')->__("Order Complete Final State")
            )
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            self::APPROVE_ORDER_POINTS_ON_DO_NOT =>
                Mage::helper('rewards')->__("Don't Approve Automatically"),
            self::APPROVE_ORDER_POINTS_ON_INVOICE =>
                Mage::helper('rewards')->__("Invoice"),
            self::APPROVE_ORDER_POINTS_ON_SHIPMENT =>
                Mage::helper('rewards')->__("Shipment"),
            self::APPROVE_ORDER_POINTS_ON_ORDER_COMPLETE =>
                Mage::helper('rewards')->__("Order Complete Final State")
        );
    }
}