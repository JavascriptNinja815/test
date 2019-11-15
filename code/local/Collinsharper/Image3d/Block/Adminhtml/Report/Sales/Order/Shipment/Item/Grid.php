<?php

class Collinsharper_Image3d_Block_Adminhtml_Report_Sales_Order_Shipment_Item_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('report_sales_order_shipment_item_grid');
        $this->setDefaultSort('shipped_date');
        $this->setDefaultDir('DESC');
    }

    /**
     * Prepare and set collection of grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('sales/order_shipment_item_collection');
        $collection->getSelect()->joinLeft(
                array('order_item' => Mage::getSingleton('core/resource')->getTableName('sales_flat_order_item')),
                'order_item.item_id = main_table.order_item_id',
                array('base_discount_amount', 'base_row_total', 'qty_ordered', 'price',
			'items_price' => new Zend_Db_Expr('(qty_ordered * order_item.price)'))
            )->joinLeft(
                array('shipment' => Mage::getSingleton('core/resource')->getTableName('sales_flat_shipment')),
                'shipment.entity_id = main_table.parent_id',
                array('shipped_date' => 'created_at')
            )->joinLeft(
                array('order' => Mage::getSingleton('core/resource')->getTableName('sales_flat_order')),
                'shipment.order_id = order.entity_id',
                array('created_at', 'order_source', 'base_currency_code', 'base_subtotal', 'order_number' => 'increment_id', 'order_id' => 'entity_id', 'base_shipping_amount',
                'tax_amount' => new Zend_Db_Expr('order.tax_amount')
                )
            );
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
            'filter_index'  => 'order.created_at',
            'format'    => $dateFormatIso,
            'index'     => 'created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('shipped_date', array(
            'header'    => Mage::helper('sales')->__('Shipped Date'),
            'filter_index'  => 'shipment.created_at',
            'format'    => $dateFormatIso,
            'index'     => 'shipped_date',
            'type'      => 'datetime',
        ));

  $this->addColumn('order_source', array(
            'header'    => Mage::helper('sales')->__('Platform'),
            'index'     => 'order_source',
            'type'      => 'options',
            'options'   => array('Web' => 'Web', 'iOS' => 'iOS', 'Android' => 'Android'),
            'width'     => '100px',
            'renderer'  => 'chimage3d/adminhtml_report_sales_order_item_grid_renderer_platform',
            'filter_condition_callback' => array($this, '_filterPlatformCallback')
        ));


	$this->addColumn('order_number', array(
            'header'    => Mage::helper('sales')->__('Order #'),
            'filter_index'  => 'order.increment_id',
            'index'     => 'order_number',
            'type'      => 'text',
        ));

        $this->addColumn('sku', array(
            'header'    => Mage::helper('sales')->__('SKU'),
            'filter_index'  => 'main_table.sku',
            'index'     => 'sku',
        ));

        $this->addColumn('qty', array(
            'header'    => Mage::helper('sales')->__('Quantity Sold/Shipped'),
            'index'     => 'qty_ordered',
            'type'      => 'number',
        ));

	$this->addColumn('price', array(
            'header'    => Mage::helper('sales')->__('Full Price'),
            'index'     => 'price',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
            'filter'    => false
        ));

//Gross Product from shipped invoice shipped orders
	$this->addColumn('items_price', array(
            'header'    => Mage::helper('sales')->__('Gross Sales'),
            'index'     => 'items_price',
            'type'      => 'currency',
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

/*
	$this->addColumn('base_shipping_amount', array(
            'header'    => Mage::helper('sales')->__('Shipping Total'),
            'index'     => 'base_shipping_amount',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
	    'filter'    => false
        ));
*/

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
