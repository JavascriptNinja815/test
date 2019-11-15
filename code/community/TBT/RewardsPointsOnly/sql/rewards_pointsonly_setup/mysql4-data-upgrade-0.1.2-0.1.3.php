<?php

$installer = $this;

$installer->startSetup();

$query = <<<QUOTEITEMSQUERY
    SELECT main_table.item_id, main_table.quote_id
    FROM {$installer->getTable('sales/quote_item')} as main_table
    INNER JOIN {$installer->getTable('sales/quote')} as quote on quote.entity_id = main_table.quote_id AND quote.is_active=1
    WHERE main_table.row_total < 0.00001 AND main_table.row_total > -0.00001 AND main_table.redeemed_points_hash IS NOT NULL AND main_table.parent_item_id IS NULL
QUOTEITEMSQUERY;

$quoteItemIds = $installer->getConnection()->fetchAll($query);

foreach ($quoteItemIds as $quoteItemId) {
    try {
        $quoteItem = Mage::getModel('sales/quote_item')->load($quoteItemId['item_id']);

        if (!$quoteItem || !$quoteItem->getId()) {
            continue;
        }

        $quote = Mage::getModel('sales/quote')->load($quoteItemId['quote_id']);

        if (!$quote || !$quote->getId()) {
            continue;
        }

        $quoteItem->setQuote($quote);

        $redeemedPoints = Mage::helper('rewards')->unhashIt($quoteItem->getRedeemedPointsHash());

        $found = false;

        foreach ($redeemedPoints as $key => &$redemptionInstance) {
            $redemptionInstance = (array) $redemptionInstance;
            $ruleId = $redemptionInstance['rule_id'];

            if (
                $ruleId
                && (float)$quoteItem->getRowTotal() === 0.0 && (float)$quoteItem->getRowTotalInclTax() === 0.0
                && $quoteItem->getCustomPrice() !== null && (float)$quoteItem->getCustomPrice() === 0.0
            ) {
                $found = true;
                break;
            }
        }

        if ($found) {
            $quoteChildrenItems = $quote->getItemsCollection()
                ->getItemsByColumnValue('parent_item_id', $quoteItem->getId());
            
            foreach ($quoteChildrenItems as $quoteChildItem) {
                $quoteChildItem->setCustomPrice(null);
                $quoteChildItem->setOriginalCustomPrice(null);
                $quoteChildItem->setRedeemedPointsHash(null);
                $quoteChildItem->save();
            }

            $quoteItem->setCustomPrice(null);
            $quoteItem->setOriginalCustomPrice(null);
            $quoteItem->setRedeemedPointsHash(null);
            $quoteItem->save();
        }
    } catch (Exception $exc) {
        Mage::logException($exc);
    }
}

$installer->endSetup();
