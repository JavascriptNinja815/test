<?php

class Collinsharper_Image3d_Block_Adminhtml_Report_Sales_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('report_sales_order_grid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');        
    }

    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for created_at
        if ($column->getId() == 'created_at') {
            $dateCreatedAt = $column->getFilter()->getValue();
            if ($dateCreatedAt["from"]
                && $dateCreatedAt["to"]
            ){
                $this->getCollection()->addFieldToFilter('main_table.created_at', array('from' => $dateCreatedAt["from"], 'to' => $dateCreatedAt["to"], 'date' => true));
            } else {
                if ($dateCreatedAt["from"]) {
                    $this->getCollection()->addFieldToFilter('main_table.created_at', array('from' => $dateCreatedAt["from"], 'date' => true));
                } else {
                    if ($dateCreatedAt["to"]) {
                        $this->getCollection()->addFieldToFilter('main_table.created_at', array('to' => $dateCreatedAt["to"], 'date' => true));
                    }
                }
            }


            Mage::Log(__FILE__. " " .__LINE__ . " SQL: ". $this->getCollection()->getSelect()->__ToString(), null, 'report.log');
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * Prepare and set collection of grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('sales/order_grid_collection');

        $collection->getSelect()->joinLeft(
            array('flat' => Mage::getSingleton('core/resource')->getTableName('sales_flat_order')),
            'flat.entity_id = main_table.entity_id',
            array(
                'base_subtotal',
//                'shipping_total' => 'base_shipping_amount',
                'shipping_total' => new Zend_Db_Expr('if(abs(flat.base_discount_amount) > flat.base_subtotal, flat.base_shipping_amount + (flat.base_subtotal + flat.base_discount_amount), base_shipping_amount)'),
                'base_gift_cards_invoiced' => new Zend_Db_Expr('(flat.base_gift_cards_invoiced * -1)')
            )
        )->columns(array(
//            'product_total' => new Zend_Db_Expr('(flat.base_subtotal + flat.base_discount_amount)'),
//            'product_total' => new Zend_Db_Expr('if(abs(flat.base_discount_amount) > (flat.base_subtotal - flat.base_shipping_amount), 0.00001, flat.base_subtotal + flat.base_discount_amount)'),
            'product_total' => new Zend_Db_Expr('if(abs(flat.base_discount_amount) > flat.base_subtotal, 0.00001, flat.base_subtotal + flat.base_discount_amount)'),

            'sub_total' => new Zend_Db_Expr('(flat.base_subtotal + flat.base_shipping_amount) + flat.base_discount_amount'),
            'refunded_total' => new Zend_Db_Expr('-1 * (IFNULL(flat.base_total_offline_refunded, 0) + IFNULL(flat.base_total_online_refunded, 0))'),
            'transaction_total' => new Zend_Db_Expr('flat.base_total_invoiced - ((IFNULL(flat.base_total_offline_refunded, 0) + IFNULL(flat.base_total_online_refunded, 0)))'),
             'tax_amount' => new Zend_Db_Expr('flat.tax_amount')
        ));

        //$collection->addFieldToFilter('main_table.status', array("in" => array('complete')));
        //            ->addFieldToFilter('flat.base_total_invoiced - (IFNULL(flat.base_total_offline_refunded, 0) + IFNULL(flat.base_total_online_refunded, 0))', array("neq" => 0));

//        die(__FILE__. " " .__LINE__ . " SQL: ". $collection->getSelect()->__ToString());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare and add columns to grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        // Setting up the date format display
        if ($this->_isExport) {
            $dateFormatIso = Mage::app()->getLocale()->getDateFormat(
                Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM
            );
        } else {
            $dateFormatIso = Mage::app()->getLocale()->getDateTimeFormat(
                Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM
            );
        }

        $this->addColumn('created_at', array(
            'header'    => Mage::helper('sales')->__('Order Date'),
            'filter_index'  => 'main_table.created_at',
            'format'    => $dateFormatIso,
            'index'     => 'created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('order_number', array(
            'header'    => Mage::helper('sales')->__('Order #'),
            'filter_index'  => 'main_table.increment_id',
            'index'     => 'increment_id',
            'type'      => 'text',
        ));

        $this->addColumn('billing_name', array(
            'header'    => Mage::helper('sales')->__('Customer Name'),
            'index'     => 'billing_name',
        ));

        $this->addColumn('product_total', array(
            'header'    => Mage::helper('sales')->__('Product Total'),
            'index'     => 'product_total',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
            'filter'    => false
        ));

        $this->addColumn('shipping_total', array(
            'header'    => Mage::helper('sales')->__('Shipping Total'),
            'filter_index'  => 'flat.base_shipping_amount',
            'index'     => 'shipping_total',
            'type'      => 'currency',
            'currency'  => 'base_currency_code'
        ));

        $this->addColumn('sub_total', array(
            'header'    => Mage::helper('sales')->__('Sub Total'),
            'filter'    => false,
            'index'     => 'sub_total',
            'type'      => 'currency',
            'currency'  => 'base_currency_code'
        ));

        $this->addColumn('base_gift_cards_invoiced', array(
            'header'    => Mage::helper('sales')->__('Gift Card/Discount Amount'),
            'filter_index'  => 'flat.base_gift_cards_invoiced',
            'index'     => 'base_gift_cards_invoiced',
            'type'      => 'currency',
            'currency'  => 'base_currency_code'
        ));

        $this->addColumn('base_grand_total', array(
            'header'    => Mage::helper('sales')->__('Order Total'),
            'filter_index'  => 'main_table.base_grand_total',
            'index'     => 'base_grand_total',
            'type'      => 'currency',
            'currency'  => 'base_currency_code'
        ));


        $this->addColumn('refunded_total', array(
            'header'    => Mage::helper('sales')->__('Total Refunded'),
            'filter'    => false,
            'index'     => 'refunded_total',
            'type'      => 'currency',
            'currency'  => 'base_currency_code'
        ));

        $this->addColumn('transaction_total', array(
            'header'    => Mage::helper('sales')->__('Sales Total'),
            'filter'    => false,
            'index'     => 'transaction_total',
            'type'      => 'currency',
            'currency'  => 'base_currency_code'
        ));
        $this->addColumn('tax_amount', array(
            'header'    => Mage::helper('sales')->__('Total Tax'),
            'filter'    => false,
            'index'     => 'tax_amount',
            'type'      => 'currency',
            'currency'  => 'base_currency_code'
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

        return parent::_prepareColumns();
    }

    /**
     * Get url for row
     *
     * @param string $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/sales_order/view',
            array(
                'order_id'=> $row->getId(),
            )
        );
    }
}
