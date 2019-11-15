<?php

class Collinsharper_Image3d_Model_Deposit extends Collinsharper_Image3d_Model_Abstract
{

    public function _construct()
    {
        $this->_init('chimage3d/deposit');
        return parent::_construct();
    }

    public function updateToday()
    {
        $this->_updatePayments(true);
        $this->_updateGiftCards(true);

        return $this;
    }

    public function updateAll()
    {
        $this->_updatePayments();
        $this->_updateGiftCards();

        return $this;
    }

    /**
     * Updates the deposit table entries from payments.
     * Skips orders that were free.
     *
     * @return this
     */
    protected function _updatePayments()
    {
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');
        $sql = "
            REPLACE INTO `{$resource->getTableName('chimage3d/deposit')}`
                (`order_id`, `payment_id`, `ordered_at`, `shipped_at`, `amount_received`, `payment_method`)
                SELECT
                    `order`.`entity_id` AS `order_id`,
                    `main_table`.`entity_id` as `payment_id`,
                    `order`.`created_at` AS `ordered_at`,
                    `shipment`.`created_at` AS `shipped_at`,
                    `main_table`.`base_amount_paid` AS `amount_received`,
                    IF(TRIM(COALESCE(`main_table`.`cc_type`)) <>'', `main_table`.`cc_type`, `main_table`.`method`) AS `payment_method`
                FROM `{$resource->getTableName('sales/order_payment')}` AS `main_table`
                LEFT JOIN `{$resource->getTableName('sales/order')}` AS `order`
                    ON `order`.`entity_id` = `main_table`.`parent_id`
                LEFT JOIN `{$resource->getTableName('sales/shipment')}` AS `shipment`
                    ON `shipment`.`order_id` = `main_table`.`parent_id`
                WHERE `main_table`.`method` != 'free'
        ";
        $write->query($sql);

        try {
            $this->_updateAuthorizenet();
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('chimage3d')->__(
                'Payments made via Authorize.Net may not be displayed correctly. Please try clicking Refresh Stats again. The error has been logged.'
            ));
        }

        return $this;
    }

    /**
     * Finds authorizenet deposits and updates them with proper credit card types,
     * gleaned from the additional_information column of the payment table.
     *
     * @return this
     */
    protected function _updateAuthorizenet()
    {
        $collection = Mage::getModel('chimage3d/deposit')->getCollection()
            ->addFieldToFilter('payment_method', 'authorizenet');
        $collection->getSelect()->joinLeft(
            array('payment' => Mage::getSingleton('core/resource')->getTableName('sales_flat_order_payment')),
            'payment.parent_id = main_table.order_id',
            array('additional_information')
        );
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');

        foreach ($collection as $deposit) {
            $info = unserialize($deposit->getAdditionalInformation());
            if (!isset($info['authorize_cards']) || !is_array($info['authorize_cards'])) {
                continue;
            }
            $card = array_pop($info['authorize_cards']);
            $paymentMethod = 'Authorize.Net';
            if (isset($card['cc_type'])) {
                $paymentMethod = $card['cc_type'];
            }
            $sql = "
                UPDATE `{$resource->getTableName('chimage3d/deposit')}`
                SET `payment_method` = '{$paymentMethod}'
                WHERE `deposit_id` = {$deposit->getDepositId()}
            ";
            $write->query($sql);
        }

        return $this;
    }

    /**
     * Updates the deposit table from gift cards.
     *
     * @return this
     */
    protected function _updateGiftCards()
    {
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');
///        $history = Mage::getResourceModel('enterprise_giftcardaccount/history_collection')
      //      ->addFieldToFilter('main_table.action', Enterprise_GiftCardAccount_Model_History::ACTION_USED);

        $history = Mage::getModel('aw_giftcard/history')
            ->getCollection();
        $history->addFieldToFilter('main_table.action', AW_Giftcard_Model_Source_Giftcard_History_Action::USED_VALUE);


        foreach ($history as $h) {
            $matches = array();
            $additionalInfo = $h->getAdditionalInfo();
//            $success = preg_match('/Order #([0-9]+)\./', $h->getAdditionalInfo(), $matches);
            //

            $hasMessage = is_array($additionalInfo) && isset($additionalInfo["message_type"]);
            $validMessage = $hasMessage && $additionalInfo["message_type"] == 1 && isset($additionalInfo["message_data"]) &&
            is_numeric($additionalInfo["message_data"]);
            if (!$validMessage) {
                continue;
            }
            $orderNumber = $additionalInfo["message_data"];
            $sql =
                "REPLACE INTO `{$resource->getTableName('chimage3d/deposit')}`
                    (`order_id`, `ordered_at`, `shipped_at`, `amount_received`, `payment_method`)
                    SELECT
                        `order`.`entity_id` as `order_id`,
                        `order`.`created_at` AS `ordered_at`,
                        `shipment`.`created_at` AS `shipped_at`,
                        -(`main_table`.`balance_delta`) AS `amount_received`,
                        'giftcard' as `payment_method`
                    FROM `{$resource->getTableName('aw_giftcard/history')}` AS `main_table`
                    LEFT JOIN `{$resource->getTableName('sales/order')}` AS `order`
                        ON `order`.`increment_id` = '{$orderNumber}'
                    LEFT JOIN `{$resource->getTableName('sales/shipment')}` AS `shipment`
                        ON `shipment`.`order_id` = `order`.`entity_id`
                    WHERE
                        `main_table`.`history_id` = '{$h->getHistoryId()}'";
            $write->query($sql);
        }

        return $this;
    }

}
