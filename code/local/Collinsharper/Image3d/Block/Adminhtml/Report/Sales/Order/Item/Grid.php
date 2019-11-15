<?php

class Collinsharper_Image3d_Block_Adminhtml_Report_Sales_Order_Item_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('report_sales_order_item_grid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
    }

    protected function _addColumnFilterToCollection($column)
    {
        switch ($column->getId())
        {
            case 'ordered_at':
            {
                $dateCreatedAt = $column->getFilter()->getValue();
                if ($dateCreatedAt["from"]
                    && $dateCreatedAt["to"]
                ){
                    $this->getCollection()->addFieldToFilter('order.created_at', array('from' => $dateCreatedAt["from"], 'to' => $dateCreatedAt["to"], 'date' => true));
                } else {
                    if ($dateCreatedAt["from"]) {
                        $this->getCollection()->addFieldToFilter('order.created_at', array('from' => $dateCreatedAt["from"], 'date' => true));
                    } else {
                        if ($dateCreatedAt["to"]) {
                            $this->getCollection()->addFieldToFilter('order.created_at', array('to' => $dateCreatedAt["to"], 'date' => true));
                        }
                    }
                }
            }
                break;
            case 'sku_processed':
            {
                // KL: Todo we are doing excat match here, but we may want to do wildcard search instead
                if (strlen(trim($column->getFilter()->getValue())) > 0) {
                    if (strtoupper($column->getFilter()->getValue()) == 'UNKNOWN') {
                        $this->getCollection()->addFieldToFilter('sku', array('null' => true));
                    } else {
                        $this->getCollection()->addFieldToFilter('sku', array('eq' => $column->getFilter()->getValue()));
                    }
                }
            }
                break;
            default:
            {
                parent::_addColumnFilterToCollection($column);
            }
                break;
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
        // KL: Todo, we should find a more approicate way to convert the order created at to a proper timezone offset
        $timeoffset = (Mage::getModel('core/date')->calculateOffset(Mage::app()->getStore()->getConfig('general/locale/timezone')) / 3600);
        $collection = Mage::getResourceModel('chimage3d/sales_order_item_collection');
        $collection->getSelect()->columns(
            array(
                'sku_processed'     => new Zend_Db_Expr("IF (main_table.sku IS NULL, 'UNKNOWN', main_table.sku)"),
                'selling_price'     => new Zend_Db_Expr('(main_table.price - (main_table.base_discount_amount / main_table.qty_ordered))'),
                'quantity'          => new Zend_Db_Expr('SUM(main_table.qty_ordered)'),
                'quantity_refunded' => new Zend_Db_Expr('SUM(main_table.qty_refunded) + SUM(main_table.qty_canceled)'),
                'sum_amount_refunded'          => new Zend_Db_Expr('SUM(main_table.amount_refunded)'),
            ))->joinLeft(
                array('order' => Mage::getSingleton('core/resource')->getTableName('sales_flat_order')),
                'order.entity_id = main_table.order_id',
                array('ordered_at' => 'order.created_at', 'base_currency_code', 'order_source')
            )
            ->group("date(CONVERT_TZ(order.created_at, '+0:00', '" .  $timeoffset . ":00'))") // We need to Date function so we group sales by date not datetime
            ->group('main_table.sku')
            ->group('selling_price');

        $this->setCollection($collection);
	mage::log(__METHOD__ .  (string)$collection->getSelect());
        return parent::_prepareCollection();
    }

    /**
     * Prepare and add columns to grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(
                Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM
        );

        $this->addColumn('ordered_at', array(
            'header'    => Mage::helper('sales')->__('Order Date'),
            'filter_index'  => 'ordered_at',
            'format'    => $dateFormatIso,
            'index'     => 'ordered_at',
            'type'      => 'datetime',
        ));

  $this->addColumn('order_source', array(
            'header'    => Mage::helper('sales')->__('Platform'),
            'index'     => 'order.order_source',
            'type'      => 'options',
            'options'   => array('Web' => 'Web', 'iOS' => 'iOS', 'Android' => 'Android'),
            'width'     => '100px',
            'renderer'  => 'chimage3d/adminhtml_report_sales_order_item_grid_renderer_platform',
            'filter_condition_callback' => array($this, '_filterPlatformCallback')
        ));


        $this->addColumn('sku_processed', array(
            'header'    => Mage::helper('sales')->__('SKU'),
            'index'     => 'sku_processed',
            'filter_index'  => 'sku_processed',
        ));

        $this->addColumn('quantity', array(
            'header'    => Mage::helper('sales')->__('Quantity Sold'),
            'index'     => 'quantity',
            'type'      => 'number',
            'filter'    => false
        ));

        $this->addColumn('quantity_refunded', array(
            'header'    => Mage::helper('sales')->__('Quantity Refunded or Cancelled'),
            'index'     => 'quantity_refunded',
            'type'      => 'number',
            'filter'    => false
        ));

        $this->addColumn('base_price', array(
            'header'    => Mage::helper('sales')->__('Base Price'),
            'index'     => 'base_price',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
            'filter'    => false
        ));


        $this->addColumn('base_discount_amount', array(
            'header'    => Mage::helper('sales')->__('Total Discount'),
            'index'     => 'base_discount_amount',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
            'filter'    => false
        ));

        $this->addColumn('selling_price', array(
            'header'    => Mage::helper('sales')->__('Selling Price (Approx.)'),
            'index'     => 'selling_price',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
            'filter'    => false
        ));

        $this->addColumn('sum_amount_refunded', array(
            'header'    => Mage::helper('sales')->__('Refunded Amount (Approx.)'),
            'index'     => 'sum_amount_refunded',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
            'filter'    => false
        ));



        $this->addColumn('extended_value', array(
            'header'    => Mage::helper('sales')->__('Extended Value'),
            'index'     => 'extended_value',        // this gets calculated and added to the row by the renderer
            'type'      => 'currency',
            'renderer'  => 'chimage3d/adminhtml_report_sales_order_item_grid_renderer_extendedValue',
            'currency'  => 'base_currency_code',
            'filter'    => false
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
                'order_id'=> $row->getOrderId(),
            )
        );
    }

    /**
     * Retrieve Grid data as CSV
     *
     * @return string
     */
    public function getCsv()
    {
        $csv = '';
        $this->_isExport = true;
        $this->_prepareGrid();
        $this->getCollection()->getSelect()->limit();
        $this->getCollection()->setPageSize(0);

        $this->getCollection()->load();
        $this->_afterLoadCollection();

        $data = array();
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem()) {
                $data[] = '"'.$column->getExportHeader().'"';
            }
        }
        $csv.= implode(',', $data)."\n";

        foreach ($this->getCollection() as $item) {
            $data = array();
            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $data[] = '"' . str_replace(array('"', '\\'), array('""', '\\\\'),
                        $column->getRowFieldExport($item)) . '"';
                }
            }
            $csv.= implode(',', $data)."\n";
        }

        if ($this->getCountTotals())
        {
            $data = array();
            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $data[] = '"' . str_replace(array('"', '\\'), array('""', '\\\\'),
                        $column->getRowFieldExport($this->getTotals())) . '"';
                }
            }
            $csv.= implode(',', $data)."\n";
        }

        return $csv;
    }


/**
     * Filter Callback for Platform Column
     * @param $collection
     * @param $column
     * @return $this
     */
    protected function _filterPlatformCallback($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == 'Web') {
            $this->getCollection()->getSelect()->where(
                "order.order_source = 'Web' OR order.order_source = '' OR order.order_source IS NULL");
        }
        else {
            $this->getCollection()->getSelect()->where(
                "order.order_source = '$value'");
        }

        return $this;
    }

}
