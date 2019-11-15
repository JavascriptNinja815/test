<?php

class Collinsharper_Image3d_Block_Adminhtml_Report_Sales_Order_Payment_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('report_sales_order_payment_grid');
        $this->setDefaultSort('ordered_at');
        $this->setDefaultDir('DESC');
    }

    /**
     * Prepare and set collection of grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('chimage3d/deposit_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare and add columns to grid
     * Sorting is disabled because of the table schema; the primary key causes sorting to fail.
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

        $this->addColumn('order_date', array(
            'header'    => Mage::helper('sales')->__('Order Date'),
            'index'     => 'ordered_at',
            'format'    => $dateFormatIso,
            'type'      => 'datetime',
            'sortable'  => false
        ));

        $this->addColumn('shipped_date', array(
            'header'    => Mage::helper('sales')->__('Shipped Date'),
            'index'     => 'shipped_at',
            'format'    => $dateFormatIso,
            'type'      => 'datetime',
            'sortable'  => false
        ));

        $currencyCode = Mage::app()->getStore()->getBaseCurrencyCode();

        $this->addColumn('amount_received', array(
            'header'    => Mage::helper('sales')->__('Payment/Deposit Received'),
            'index'     => 'amount_received',
            'type'      => 'currency',
            'currency_code'  => $currencyCode,
            'sortable'  => false
        ));

        $this->addColumn('payment_method', array(
            'header'    => Mage::helper('sales')->__('Payment Method'),
            'index'     => 'payment_method',
            'renderer'  => 'chimage3d/adminhtml_report_sales_order_payment_grid_renderer_paymentMethod',
            'type'      => 'options',
            'options'   => Mage::helper('chimage3d')->getPaymentMethodOptionArray(true),
            'sortable'  => false
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
   

}
