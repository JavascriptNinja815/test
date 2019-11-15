<?php
/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).
 * The Sweet Tooth License is available at this URL:
 *      https://www.sweettoothrewards.com/terms-of-service
 * The Open Software License is available at this URL:
 *      http://opensource.org/licenses/osl-3.0.php
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
 * @package    [TBT_RewardsSocial2]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com
 */

/**
 * Social Migration helper
 *
 * @category   TBT
 * @package    TBT_RewardsSocial2
 * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_RewardsSocial2_Helper_Migration extends Mage_Core_Helper_Abstract 
{
    /**
     * Sql Page Limit
     */
    const LIMIT = 100;
    
    /**
     * Migration Log File
     */
    const LOG_FILE = 'social-migration.log';


    /**
     * Cache whether or not any records exist in new table.
     * @see TBT_RewardsSocial2_Helper_Migration::hasMigrationStarted()
     * @var boolean
     */
    protected $_hasMigrationStarted = null;

    /**
     * Migrate old social rewards data
     * 
     * @param string $action | what kind of social data are we migrating? (likes, tweets, etc.)
     * @param int $page | page number for sql transactions
     * @return boolean
     */
    public function migrateData($action, $page)
    {
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $table = $this->getTableForAction($action);
        $errorsFound = false;
        
        try {
            $select = $read->select()
                ->from(array('e' => $table))
                ->limit(self::LIMIT);

            $entries = $read->fetchAll($select);

            foreach ($entries as $entry) {
                // Saving action entry (migrating data from v1 to v2)
                $actionModel = Mage::getModel('rewardssocial2/action')
                    ->setCustomerId($this->fetchDataForAction('customer_id', $action, $entry))
                    ->setCreatedAt($this->fetchDataForAction('created_at', $action, $entry))
                    ->setAction($this->fetchDataForAction('action', $action, $entry))
                    ->setExtra($this->fetchDataForAction('extra', $action, $entry))
                    ->save();

                // Fetching and Loading Transfer Model
                $transfer = null;
                if ($action === 'twitter_follow') {
                    if (isset($entry['is_following']) && $entry['is_following']) {
                        $transfer = Mage::getModel('rewardssocial2/transfer')->loadByCustomerAndReason(
                            $this->fetchDataForAction('customer_id', $action, $entry),
                            Mage::helper('rewards/transfer_reason')->getReasonId('social_twitter_follow')
                        );
                    }
                } else {
                    $transfer = Mage::getModel('rewardssocial2/transfer')->loadFromData(
                        $this->fetchDataForAction('customer_id', $action, $entry),
                        $this->fetchDataForAction('entry_id', $action, $entry),
                        $this->fetchDataForAction('new_reason_id', $action, $entry)
                    );
                }

                // Updating Transfer Reason Id
                if ($transfer && $transfer->getId()) {
                    $transfer->setReferenceId($actionModel->getId())
                        ->setReasonId($this->fetchDataForAction('new_reason_id', $action, $entry))
                        ->setSkipUpdatedAt(true)
                        ->save();
                }

                // Deleting old entry
                $condition = null;
                if ($action === 'twitter_follow') {
                    $condition = "customer_id = {$this->fetchDataForAction('customer_id', $action, $entry)}";
                } else {
                    $columnName = $action . '_id';
                    $condition = "{$columnName} = {$this->fetchDataForAction('entry_id', $action, $entry)}";
                }

                $write = Mage::getSingleton('core/resource')->getConnection('core_write');
                $write->delete($table, $condition);
            }
        } catch (Exception $e) {
            $errorsFound = true;
            Mage::log($e->__toString(), Zend_Log::ERR, self::LOG_FILE);
        }
        
        return !$errorsFound;
    }
    
    /**
     * Generic data getter based on action type for migration purposes
     * 
     * @param string $attribute
     * @param string $action
     * @param array $entry
     * @return string
     */
    protected function fetchDataForAction($attribute, $action, $entry = null)
    {
        switch ($attribute) {
            case 'customer_id': 
                return $entry['customer_id'];
            case 'created_at':
                $createdAt = (isset($entry['created_time'])) ? $entry['created_time'] : null;
                return $createdAt;
            case 'action':
                if ($action === 'referral_share') {
                    return 'facebook_share_referral';
                } elseif ($action === 'purchase_share') {
                    return ($entry['type_id'] == 1) ? 'facebook_share_purchase' : 'twitter_tweet_purchase';
                } else {
                    return $action;
                }
            case 'extra':
                switch ($action) {
                    case 'facebook_like':
                    case 'twitter_tweet':
                    case 'google_plusone':
                        return $entry['url'];
                    case 'twitter_follow':
                    case 'share_referral':
                        return null;
                    case 'facebook_share':
                        return $entry['product_id'];
                    case 'pinterest_pin':
                        return $entry['pinned_url'];
                    case 'purchase_share':
                        $extra = array(
                            'product' => $entry['product_id'],
                            'order' => $entry['order_id']
                        );
                        
                        return json_encode($extra);
                }
            case 'entry_id': 
                $key = $action . '_id';
                return (isset($entry[$key])) ? $entry[$key] : null;
            case 'new_reason_id':
                switch ($action) {
                    case 'facebook_like':
                        return Mage::helper('rewards/transfer_reason')->getReasonId('social_facebook_like');
                    case 'facebook_share':
                        return Mage::helper('rewards/transfer_reason')->getReasonId('social_facebook_share');
                    case 'twitter_tweet':
                        return Mage::helper('rewards/transfer_reason')->getReasonId('social_twitter_tweet');
                    case 'twitter_follow':
                        return Mage::helper('rewards/transfer_reason')->getReasonId('social_twitter_follow');
                    case 'google_plusone':
                        return Mage::helper('rewards/transfer_reason')->getReasonId('social_google_plusone');
                    case 'pinterest_pin':
                        return Mage::helper('rewards/transfer_reason')->getReasonId('social_pinterest_pin');
                    case 'referral_share':
                        return Mage::helper('rewards/transfer_reason')->getReasonId('social_referral_share');
                    case 'purchase_share':
                        return ($entry['type_id'] == 1)
                            ? Mage::helper('rewards/transfer_reason')->getReasonId('social_facebook_share_purchase')
                            : Mage::helper('rewards/transfer_reason')->getReasonId('social_twitter_tweet_purchase');
                }
        }
    }
    
    /**
     * Fetch the table of the old rewards social module for a given social action
     * 
     * @param type $action
     * @return string
     */
    public function getTableForAction($action)
    {
        $tablePrefix = Mage::getConfig()->getTablePrefix();
        
        switch ($action) {
            case 'facebook_like':
                return $tablePrefix . 'rewardssocial_facebook_like';
            case 'facebook_share':
                return $tablePrefix . 'rewardssocial_facebook_share';
            case 'twitter_tweet':
                return $tablePrefix . 'rewardssocial_twitter_tweet';
            case 'twitter_follow':
                return $tablePrefix . 'rewardssocial_customer';
            case 'google_plusone':
                return $tablePrefix . 'rewardssocial_google_plusone';
            case 'pinterest_pin':
                return $tablePrefix . 'rewardssocial_pinterest_pin';
            case 'referral_share':
                return $tablePrefix . 'rewardssocial_referral_share';
            case 'purchase_share':
                return $tablePrefix . 'rewardssocial_purchase_share';
        }
    }
    
    /**
     * Delete old social tables and core_resource entry
     */
    public function dropOldData()
    {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        
        $actions = array(
            'facebook_like',
            'facebook_share',
            'twitter_tweet',
            'twitter_follow',
            'google_plusone',
            'pinterest_pin',
            'referral_share',
            'purchase_share'
        );
        
        // Droping old tabled
        foreach ($actions as $action) {
            $table = $this->getTableForAction($action);
            
            $query = "DROP TABLE {$table}";
            $write->query($query);
        }
        
        // Deleting entry from `core_resource`
        $resourceTable = Mage::getSingleton('core/resource')->getTableName('core/resource');
        $query = "DELETE FROM {$resourceTable} WHERE code = 'rewardssocial_setup'";
        $write->query($query);
    }
    
    /**
     * Disable old Social Modules
     * @return boolean
     */
    public function disableSocialModules()
    {
        $helper = Mage::helper('rewards/config');

        $isDisabledModule1 = true;
        $isDisabledModule2 = $helper->disableModule('TBT_Rewardssocial');
        if (Mage::helper('core')->isModuleEnabled('Evolved_Like')) {
            $isDisabledModule1 = $helper->disableModule('Evolved_Like');
        }
        return ($isDisabledModule1 && $isDisabledModule2);
    }
    
    /**
     * Fetch a map off all social reward entries
     * 
     * @return array
     */
    public function fetchEntriesCountMap()
    {
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        
        $actionsCountMap = array(
            'facebook_like'  => 0,
            'facebook_share' => 0,
            'twitter_tweet'  => 0,
            'twitter_follow' => 0,
            'google_plusone' => 0,
            'pinterest_pin'  => 0,
            'referral_share' => 0,
            'purchase_share' => 0
        );
        
        foreach ($actionsCountMap as $key => $value) {
            $table = $this->getTableForAction($key);
            
            $select = $read->select()
                ->from(array('e' => $table), array(new Zend_Db_Expr('COUNT(*) as count')));
            
            $actionsCountMap[$key] = $read->fetchOne($select);
        }

        return $actionsCountMap;
    }

    /**
     * Will query the rewardssocial2/action table for records,
     * if any exist, we can assume migration had started
     * @return bool
     */
    public function hasMigrationStarted()
    {
        if (is_null($this->_hasMigrationStarted)) {
            $collection = Mage::getResourceModel('rewardssocial2/action_collection');
            $this->_hasMigrationStarted =  ($collection->getSize() > 0);
        }

        return $this->_hasMigrationStarted;
    }
}
