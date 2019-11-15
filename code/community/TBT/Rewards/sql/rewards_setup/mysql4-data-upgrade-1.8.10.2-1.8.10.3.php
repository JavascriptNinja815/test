<?php

/**
 * Update email notifications. Users who disable point summary or referral emails
 * in the past will have loyalty email notifications disabled.
 */

$installer = $this;
$installer->startSetup();

$entityTypeId = Mage::getModel('eav/entity')->setType('customer')->getTypeId();
$rewardsAttribute = Mage::getModel('eav/config')->getAttribute('customer', 'rewards_points_notification');
$referralAttribute = Mage::getModel('eav/config')->getAttribute('customer', 'rewardsref_notify_on_referral');
$tableName = Mage::getSingleton('core/resource')->getTableName('customer_entity_int');

if (
    $rewardsAttribute
    && $referralAttribute
    && $rewardsAttribute->getId()
    && $referralAttribute->getId()
) {
    $rewardsAttributeId = $rewardsAttribute->getId();
    $referralAttributeId = $referralAttribute->getId();
    $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
    
    $where = array(
        $connection->quoteInto('attribute_id = ?', $rewardsAttributeId),
        $connection->quoteInto('value = ?', 1)
    );
    $connection->delete($tableName, $where);
    
    $sql = "
        INSERT IGNORE INTO {$tableName}(entity_type_id, attribute_id, entity_id, value)
        SELECT 
            1 AS entity_type_id,
            {$rewardsAttributeId} AS attribute_id,
            entity_id,
            0 AS value
        FROM {$tableName}
        WHERE attribute_id = {$referralAttributeId}
        AND value = 0;
    ";
        
    $connection->query($sql);
    $connection->commit();
}

$installer->endSetup();
