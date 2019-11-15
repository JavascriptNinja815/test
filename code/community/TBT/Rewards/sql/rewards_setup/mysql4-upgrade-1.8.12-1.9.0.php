<?php

$installer = $this;
$installer->startSetup();

$helper = Mage::helper('rewards');
$transfersCount = Mage::getModel('rewards/transfer')->getCollection()->getSize();
$coreHelper = Mage::helper('core');

$table = $installer->getTable('rewards/transfer');
$connection = $installer->getConnection();

// Changes to rewards_transfer table
$connection->dropColumn($table, 'expire_date');
$connection->dropColumn($table, 'currency_id');
$connection->dropColumn($table, 'source_reference_id');

$this->changeColumn($table, 'status', 'status_id', "INT(11) NOT NULL DEFAULT '0'");
$this->changeColumn($table, 'creation_ts', 'created_at', 'TIMESTAMP');
$this->changeColumn($table, 'last_update_ts', 'updated_at', 'TIMESTAMP');
$this->changeColumn($table, 'last_update_by', 'updated_by', 'VARCHAR(60) NULL');
$this->modifyColumn($table, 'issued_by', 'VARCHAR(60) NULL');

$connection->addColumn($table, 'reference_id', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'length'    => '11',
    'comment'   => 'Reference ID',
));

$connection->addIndex(
    $table,
    $installer->getIdxName('rewards/transfer', array('reason_id', 'reference_id')),
    array('reason_id', 'reference_id')
);

$tableName = Mage::getSingleton('core/resource')->getTableName('rewards_transfer');
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$referenceTableName = $tableName . '_reference';

$referenceTableExist = Mage::getSingleton('core/resource')
    ->getConnection('core_write')
    ->isTableExists($referenceTableName);

if ($transfersCount > 0 && $referenceTableExist) {

    // Update reason and reference data
    $reasonsMap = array(
        -1 => 1,
        0 => 6,
        2 => 12, //pending
        3 => 7, //pending
        4 => 8, //pending
        5 => 6,
        30 => 9, //error
        31 => 2, //error
        32 => 4 //error
    );

    $referenceMap = array(
        2 => 2, // Product Reviews
        3 => 3, // Polls
        4 => 4, // Tags
        7 => 5, // Signups
        10 => 9, // Newsletter
        600 => 600, // Generic Milestone
        601 => 601, // Order Milestone
        602 => 602, // Membership Milestone
        603 => 603, // Inactivity Milestone
        604 => 604, // Referrals Milestone
        605 => 605, // Revenue Milestone
        701 => 701 // Points Earning Milestone
    );
    
    // new reason id => transfer comments (there is no other way to differentiate these transfers)
    $commentsMap = array(
        1 => $helper->__('Points adjustment made during the process of cancelling an order.'),
        13 => Mage::getStoreConfig('rewards/transferComments/tellAFriendEarned')
    );

    // Update reason ids where old reason id is enough
    foreach ($reasonsMap as $oldReason => $newReason) {
        $fields = array('reason_id' => $newReason);
        $where = $write->quoteInto('reason_id = ?', $oldReason);
        $write->update($tableName, $fields, $where);
    }

    // UPDATE reason ids by reference type
    foreach ($referenceMap as $referenceTypeId => $newReasonId) {
        $sql = "UPDATE {$tableName} "
            . "SET reason_id = {$newReasonId} "
            . "WHERE rewards_transfer_id IN ("
                . "SELECT rewards_transfer_id "
                . "FROM {$referenceTableName} "
                . "WHERE reference_type = {$referenceTypeId}"
            . ")";

        $write->query($sql);
    }

    // UPDATE reference ids
    $sql = "UPDATE {$tableName} tt "
        . "INNER JOIN {$referenceTableName} rt "
        . "ON tt.rewards_transfer_id = rt.rewards_transfer_id "
        . "SET tt.reference_id = rt.reference_id";

    $write->query($sql);

    // Make sure the correct reference id is set for referal transfers (the order reference not the customer reference)
    $sql = "UPDATE {$tableName} tt "
        . "INNER JOIN {$referenceTableName} rt "
        . "ON tt.rewards_transfer_id = rt.rewards_transfer_id "
        . "SET tt.reference_id = rt.reference_id "
        . "WHERE rt.reference_type = 21";

    $write->query($sql);
}

// Migrate Social Data
if ($coreHelper->isModuleEnabled('TBT_Rewardssocial') && $coreHelper->isModuleEnabled('TBT_RewardsSocial2')) {
    $socialHelper = Mage::helper('rewardssocial2/migration');
    $actions = array('facebook_like', 'facebook_share', 'twitter_tweet', 'twitter_follow', 'google_plusone', 'pinterest_pin', 'referral_share', 'purchase_share');
    
    foreach ($actions as $socialAction) {
        $socialHelper->migrateData($socialAction, 1000);
    }
    
    try {
        $socialHelper->dropOldData();
        if (!$socialHelper->disableSocialModules()) {
            $message = $socialHelper->__("Unable to disable MageRewards Social 1.0.");
            Mage::log($message);
            Mage::getSingleton('core/session')->addError($message);
        }
    } catch (Exception $e) {
        Mage::getSingleton('core/session')->addError($e->getMessage());
        Mage::log($message);
    }
    
    Mage::app()->cleanCache();
}

// Drop reference table
$write->dropTable($referenceTableName);
$write->commit();

$installer->endSetup();

