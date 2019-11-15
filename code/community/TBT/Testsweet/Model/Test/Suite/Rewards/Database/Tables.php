<?php

class TBT_Testsweet_Model_Test_Suite_Rewards_Database_Tables extends TBT_Testsweet_Model_Test_Suite_Abstract
{
    public function getRequireTestsweetVersion()
    {
        return '1.0.0.0';
    }

    public function getSubject()
    {
        return $this->__('Check database tables');
    }

    public function getDescription()
    {
        return $this->__('Check database for required MageRewards tables and columns.');
    }

    protected function generateSummary()
    {
        $cr = Mage::getSingleton('core/resource');
        $tableChecks = array();

        $tableChecks[$cr->getTableName('rewards_currency')] = array(
            'rewards_currency_id',
            'caption',
            'value',
            'active',
            'image',
            'image_width',
            'image_height',
            'image_write_quantity',
            'font',
            'font_size',
            'font_color',
            'text_offset_x',
            'text_offset_y'
        );
        
        $tableChecks[$cr->getTableName('rewards_customer')] = array(
            'rewards_customer_id',
            'rewards_currency_id',
            'customer_entity_id'
        );
        
        $tableChecks[$cr->getTableName('rewards_special')] = array(
            'rewards_special_id',
            'name',
            'description',
            'from_date',
            'to_date',
            'customer_group_ids',
            'is_active',
            'conditions_serialized',
            'points_action',
            'points_currency_id',
            'points_amount',
            'website_ids',
            'is_rss',
            'sort_order',
            'onhold_duration',
            'simple_action'
        );
        
        $tableChecks[$cr->getTableName('rewards_store_currency')] = array(
            'rewards_store_currency_id',
            'currency_id',
            'store_id'
        );
        
        $tableChecks[$cr->getTableName('rewards_transfer')] = array(
            'rewards_transfer_id',
            'customer_id',
            'quantity',
            'comments',
            'effective_start',
            'status_id',
            'created_at',
            'reason_id',
            'updated_at',
            'issued_by',
            'updated_by',
            'is_dev_mode',
            'reference_id'
        );
        
        $tableChecks[$cr->getTableName('salesrule')] = array(
            'points_action',
            'points_discount_action',
            'points_currency_id',
            'points_amount',
            'points_discount_amount',
            'points_amount_step',
            'points_amount_step_currency_id',
            'points_qty_step',
            'points_max_qty'
        );

        $tableChecks[$cr->getTableName('sales_flat_quote')] = array(
            'cart_redemptions',
            'applied_redemptions',
            'rewards_discount_amount',
            'rewards_base_discount_amount',
            'rewards_discount_tax_amount',
            'rewards_base_discount_tax_amount',
            'rewards_valid_redemptions'
        );

        $tableChecks[$cr->getTableName('sales_flat_quote_item')] = array(
            'earned_points_hash',
            'redeemed_points_hash'
        );

        $tableChecks[$cr->getTableName('sales_flat_order')] = array(
            'rewards_discount_amount',
            'rewards_base_discount_amount',
            'rewards_discount_tax_amount',
            'rewards_base_discount_tax_amount'
        );

        $tableChecks[$cr->getTableName('sales_flat_order_item')] = array(
            'earned_points_hash',
            'redeemed_points_hash'
        );

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $debugDbSchema = array();
        foreach ($tableChecks as $table => $columns) {
            $query = "SHOW COLUMNS FROM $table";
            $table_schema = $read->fetchAll($query);
            $debugDbSchema[$table] = $table_schema;

            $table_columns = array();
            foreach ($table_schema as $column_schema) {
                $table_columns[] = $column_schema['Field'];
            }

            if (!empty($table_columns)) {
                $this->addPass($this->__("Table %s", $table));
            } else {
                $this->addFail($this->__("Table missing %s", $table));
            }

            foreach ($columns as $column) {
                if (in_array($column, $table_columns)) {
                    $this->addPass($this->__("Table %s has column %s", $table, $column));
                } else {
                    $this->addFail($this->__("Table %s is missing column %s", $table, $column));
                }
            }
        }

        $debug = filter_input(INPUT_GET, 'debug');
        if (!empty($debug)) {
            $msg .= "DEBUG DATA SCHEMA OUTPUT\n";
            $msg .= 'Magento Version: ' . Mage::getVersion() . "\n";
            $msg .= "Current MageRewards Core Database schema:\n";
            $msg .= json_encode($debugDbSchema);
            $this->addNotice($msg);
        }
    }
}

