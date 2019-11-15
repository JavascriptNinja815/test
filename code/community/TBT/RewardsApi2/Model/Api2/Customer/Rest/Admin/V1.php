<?php

/**
 * Class TBT_RewardsApi2_Model_Api2_Customer_Rest_Admin_V1
 * API implementation for the Sweet Tooth customer model
 *
 * @extends    Mage_Api2_Model_Resource
 * @category   TBT
 * @package    TBT_RewardsApi2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsApi2_Model_Api2_Customer_Rest_Admin_V1 extends Mage_Customer_Model_Api2_Customer_Rest
{
    /**
     * Retrieve information about customer
     *
     * @throws Mage_Api2_Exception
     * @return array
     */
    protected function _retrieve()
    {
        if (!Mage::helper('rewards/customer_points_index')->isUpToDate()) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }
        
        $id = $this->getRequest()->getParam('id');
        $data = parent::_retrieve();
        
        $rewardsCustomer = Mage::getModel('rewards/customer_indexer_points')->load($id);
        $data['rewards_active_balance'] = $rewardsCustomer->getCustomerPointsActive();
        $data['rewards_usable_balance'] = $rewardsCustomer->getCustomerPointsUsable();
        
        return $data;
    }
    
    /**
     * Retrieve collection instances
     * @return Mage_Customer_Model_Resource_Customer_Collection
     * @see Mage_Customer_Model_Api2_Customer_Rest::_getCollectionForRetrieve()
     */
    protected function _getCollectionForRetrieve()
    {
        if (!Mage::helper('rewards/customer_points_index')->isUpToDate()) {
            $this->_critical(self::RESOURCE_INTERNAL_ERROR);
        }
        
        $tableName = Mage::getSingleton('core/resource')->getTableName('rewards_customer_index_points');
        $collection = parent::_getCollectionForRetrieve();

        $collection->getSelect()->joinLeft(
            array('r' => $tableName), 
            'e.entity_id = r.customer_id',
            array(
                'rewards_active_balance' => 'customer_points_active',
                'rewards_usable_balance' => 'customer_points_usable'
            )
        );
        
        return $collection;
    }

    /**
     * Handler for resource creation. Not supported.
     * @param array $data
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    protected function _create(array $data){
        $this->_critical(self::RESOURCE_METHOD_NOT_IMPLEMENTED);
    }

    /**
     * Handler for resource update. Not supported.
     * @param array $data
     * @throws Exception
     * @throws Mage_Api2_Exception
     */
    protected function _update(array $data){
        $this->_critical(self::RESOURCE_METHOD_NOT_IMPLEMENTED);
    }
}
