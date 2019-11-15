<?php

class Collinsharper_Image3d_Block_Adminhtml_Report_Sales_Order_Shipment_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('report_sales_order_shipment_grid');
        $this->setDefaultSort('history.created_at');
        $this->setDefaultDir('DESC');
    }

    /**
     * Prepare and set collection of grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('chimage3d/sales_order_grid_collection');
        $collection->getSelect()->joinLeft(
            array('flat' => Mage::getSingleton('core/resource')->getTableName('sales_flat_order')),
            'flat.entity_id = main_table.entity_id',
            array('base_shipping_amount' => new Zend_Db_Expr('FORMAT((IFNULL(flat.base_shipping_amount,0) - IFNULL(flat.base_shipping_refunded,0)),4)') ,
		  'base_subtotal'  => new Zend_Db_Expr('FORMAT((IFNULL(flat.base_subtotal,0) - IFNULL(flat.base_subtotal_refunded,0)),4)'),
		  'base_discount_amount' => new Zend_Db_Expr('FORMAT((IFNULL(flat.base_discount_amount,0) - IFNULL(flat.base_discount_refunded,0)),4)'),
          'base_grand_total' => new Zend_Db_Expr('FORMAT((IFNULL(flat.base_subtotal,0) + IFNULL(flat.base_shipping_amount,0) - IFNULL(flat.base_subtotal_refunded,0) - IFNULL(flat.base_shipping_refunded,0)),4)'),
		  'base_gift_cards_amount' => new Zend_Db_Expr('FORMAT((IFNULL(flat.base_gift_cards_amount,0) - IFNULL(flat.base_gift_cards_refunded,0)),4)'),
		  'discount_description',
		  'gift_cards',
                'order_source',
		  'total_due' => new Zend_Db_Expr('if(isnull(flat.base_total_due),"0.00",flat.base_total_due)'),
            //    'base_gift_cards_invoiced' => new Zend_Db_Expr('(flat.base_gift_cards_invoiced * -1)')
                'base_gift_cards_invoiced_amnt' => new Zend_Db_Expr('((flat.base_gift_cards_amount * -1) + flat.base_discount_amount)')
            )
        )->joinLeft(
            array('history' => Mage::getSingleton('core/resource')->getTableName('sales_flat_order_status_history')),
            'history.parent_id = main_table.entity_id',
            array()
        )->columns(array(
            'shipped_at'    => new Zend_Db_Expr("(SELECT max(created_at) FROM ".Mage::getSingleton('core/resource')->getTableName('sales_flat_order_status_history')." WHERE parent_id = main_table.entity_id AND entity_name = 'shipment')"),
            'completed_at'    => new Zend_Db_Expr("(SELECT max(created_at) FROM ".Mage::getSingleton('core/resource')->getTableName('sales_flat_order_status_history')." WHERE parent_id = main_table.entity_id AND status = 'complete')"),

            'sub_total' => new Zend_Db_Expr('FORMAT((IFNULL(flat.base_subtotal,0) + IFNULL(flat.base_shipping_amount,0) - IFNULL(flat.base_subtotal_refunded,0) - IFNULL(flat.base_shipping_refunded,0)),4)')
        ))->where("history.status = '" . Mage_Sales_Model_Order::STATE_COMPLETE . "'")
        ->group('main_table.entity_id');

        Mage::Log(__FILE__. " " .__LINE__ . " SQL: ". $collection->getSelect()->__ToString(), null, 'report.log');
        $this->setCollection($collection);
        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for shipped_at
        switch ($column->getId())
        {
            case 'shipped_at':
                {
                    $dateShipAt = $column->getFilter()->getValue();
                    if ($dateShipAt["from"]
                        && $dateShipAt["to"]
                    ){
                        $this->getCollection()->addFieldToFilter("history.entity_name", array('eq' => array('shipment')));
                        $this->getCollection()->addFieldToFilter("history.created_at", array('from' => $dateShipAt["from"], 'to' => $dateShipAt["to"], 'date' => true));
                    } else {
                        if ($dateShipAt["from"]) {
                            $this->getCollection()->addFieldToFilter("history.entity_name", array('eq' => array('shipment')));
                            $this->getCollection()->addFieldToFilter('history.created_at', array('from' => $dateShipAt["from"], 'date' => true));
                        } else {
                            if ($dateShipAt["to"]) {
                                $this->getCollection()->addFieldToFilter("history.entity_name", array('eq' => array('shipment')));
                                $this->getCollection()->addFieldToFilter('history.created_at', array('to' => $dateShipAt["to"], 'date' => true));
                            }
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
     * Prepare and add columns to grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function  _prepareColumns()
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

        $this->addColumn('ocreated_at', array(
            'header'    => Mage::helper('sales')->__('Order Date'),
            'filter_index'  => 'main_table.created_at',
            'format'    => $dateFormatIso,
            'index'     => 'created_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('shipped_at', array(
            'header'    => Mage::helper('sales')->__('Ship Date'),
            'filter_index'  => 'shipped_at',
            'format'    => $dateFormatIso,
            'index'     => 'shipped_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('completed_at', array(
            'header'    => Mage::helper('sales')->__('Completion Date'),
            'filter_index'  => 'completed_at',
            'format'    => $dateFormatIso,
            'index'     => 'completed_at',
            'type'      => 'datetime',
            'filter'    => false
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

        $this->addColumn('gross_product_total', array(
            'header'    => Mage::helper('sales')->__('Gross Product Total'),
	    'filter_index' => 'flat.base_subtotal',
            'index'     => 'base_subtotal',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
            'filter'    => false
        ));

        $this->addColumn('base_shipping_amount', array(
            'header'    => Mage::helper('sales')->__('Shipping Total'),
            'filter_index'  => 'flat.base_shipping_amount',
            'index'     => 'base_shipping_amount',
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

	$this->addColumn('base_discount_amount', array(
            'header'    => Mage::helper('sales')->__('Discount Amount'),
            'filter_index' => 'flat.base_discount_amount',
            'index'     => 'base_discount_amount',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
            'filter'    => false
        ));

        $this->addColumn('discount_description', array(
            'header'    => Mage::helper('sales')->__('Discount Code'),
            'filter_index' => 'flat.discount_description',
            'index'     => 'discount_description',
            'type'      => 'text'
        ));

        $this->addColumn('base_gift_cards_amount', array(
            'header'    => Mage::helper('sales')->__('Gift Cards Amount'),
            'filter_index'  => 'flat.base_gift_cards_amount',
            'index'     => 'base_gift_cards_amount',
            'type'      => 'currency',
            'currency'  => 'base_currency_code'
        ));

	$this->addColumn('gift_cards', array(
            'header'    => Mage::helper('sales')->__('Gift Cards Code'),
	    'filter'    => false,
            'index'     => 'gift_cards',
            'type'      => 'text',
            'renderer'  => 'chimage3d/adminhtml_report_sales_order_shipment_grid_renderer_giftcardCodes'
        ));

        $this->addColumn('base_grand_total', array(
            'header'    => Mage::helper('sales')->__('Order Total'),
            'filter_index'  => 'main_table.base_grand_total',
            'index'     => 'base_grand_total',
            'type'      => 'currency',
            'currency'  => 'base_currency_code'
        ));

	 $this->addColumn('total_due', array(
            'header'    => Mage::helper('sales')->__('Balance Owed'),
            'index'     => 'total_due',
            'type'      => 'currency',
            'currency'  => 'base_currency_code',
            'filter'    => false
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

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

	// 20190912 - patch for order source columns
        return parent::_prepareColumns();
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
                "order_source = 'Web' OR order_source = '' OR order_source IS NULL");
        }
        else {
            $this->getCollection()->getSelect()->where(
                "order_source = '$value'");
        }

        return $this;
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
