<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).

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
 * Mysql Transfer
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Model_Mysql4_Transfer extends Mage_Core_Model_Mysql4_Abstract {
    public function _construct() {
        // Note that the rewards_transfer_id refers to the key field in your database table.
        $this->_init ( 'rewards/transfer', 'rewards_transfer_id' );
    }

    /**
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _beforeLoad(Mage_Core_Model_Abstract $object) {
        $this->getSelect ()->join ( array ('cps' => 'customer_entity' ), $this->getMainTable () . '.customer_id = cps.entity_id' );
        $this->getSelect ()->join ( array ('cpsv' => 'customer_entity_varchar' ), 'cps.entity_id = cpsv.entity_id AND cpsv.attribute_id = 1' );
        // Append the associated orders for this transfer


        return parent::_beforeLoad ( $object );
    }

    /**
     * Retrieve select object for load object data
     *
     * @param string $field
     * @param mixed $value
     * @return Zend_Db_Select
     */
    protected function _getLoadSelect($field, $value, $object) {

        $select = parent::_getLoadSelect ( $field, $value, $object );
        return $select;
    }

    /**
     * Will accept a customer object, and return it's last *active* points transaction
     * @param int $customerId Customer ID
     * @return TBT_Rewards_Model_Transfer|null last active transaction
     */
    public function getLastActiveTransaction($customerId)
    {
        $collectionModel = Mage::getResourceModel('rewards/transfer_collection')
            ->addFieldToFilter('customer_id', array('eq' => $customerId))
            ->selectOnlyActive()
            ->addOrder('updated_at', Varien_Data_Collection::SORT_ORDER_DESC);
        $collectionModel->getSelect()->limit(1);
        return $collectionModel->getFirstItem();
    }

}
