<?php

class Collinsharper_Image3d_Block_Adminhtml_Report_GiftCard_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('report_giftCard_grid');
        $this->setDefaultSort('main_table.updated_at');
        $this->setDefaultDir('DESC');
    }

    /**
     * Prepare and set collection of grid
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('aw_giftcard/history')->getCollection();
        $collection->addFieldToFilter('main_table.action', AW_Giftcard_Model_Source_Giftcard_History_Action::USED_VALUE); // 3 is used..

        $collection->getSelect()->join(
                array('account' => Mage::getSingleton('core/resource')->getTableName('aw_giftcard')),
                'account.entity_id = main_table.giftcard_id',
                array('date_created' => 'created_at', 'website_id', 'code', 'redemption_code_cost')
            )->join(
                array('history' => Mage::getSingleton('core/resource')->getTableName('aw_giftcard_history')),
                "history.giftcard_id = main_table.giftcard_id AND history.action = '" . AW_Giftcard_Model_Source_Giftcard_History_Action::CREATED_VALUE . "'",
                array('purchase_balance_amount' => 'balance_amount', 'purchase_date' => 'updated_at')
            );

        $collection->getSelect()->group('main_table.history_id');

        //die(' we have SQL "' . $collection->getSelect()->__toString());

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

        $this->addColumn('purchase_date', array(
            'header'    => Mage::helper('sales')->__('Purchase Date'),
            'filter_index'  => 'history.updated_at',
            'format'    => $dateFormatIso,
            'index'     => 'purchase_date',
            'type'      => 'datetime',
        ));
		
		$this->addColumn('code', array(
			'header' => Mage::helper('sales')->__('Code'),
			'filter_index'  => 'account.code',
			'index' => 'code'
		));

        $this->addColumn('order_number', array(
            'header'    => Mage::helper('sales')->__('Order #'),
            'filter_index'  => 'main_table.additional_info',
            'renderer' => 'Collinsharper_Image3d_Block_Adminhtml_Report_Giftcardordercolumn',
            'index'     => 'additional_info',
            'type'      => 'text',
        ));

        $currencyCode = Mage::app()->getStore()->getBaseCurrencyCode();

		$this->addColumn('redemption_code_cost', array(
                'header'    => Mage::helper('sales')->__('Redemption Code Cost'),
                'filter_index'  => 'main_table.redemption_code_cost',
                'index'     => 'redemption_code_cost',
                'type'      => 'currency',
                'currency_code' => $currencyCode,
        ));

        $this->addColumn('purchase_balance_amount', array(
            'header'    => Mage::helper('sales')->__('Purchase Price'),
            'filter_index'  => 'history.balance_amount',
            'index'     => 'purchase_balance_amount',
            'type'      => 'currency',
            'currency_code' => $currencyCode,
        ));

        $this->addColumn('redeemed_date', array(
            'header'    => Mage::helper('sales')->__('Redeemed Date'),
            'filter_index'  => 'main_table.updated_at',
            'format'    => $dateFormatIso,
            'index'     => 'updated_at',
            'type'      => 'datetime',
        ));

        $this->addColumn('redeemed_price', array(
            'header'    => Mage::helper('sales')->__('Redeemed Amount'),
            'filter_index'  => 'main_table.balance_delta',
            'index'     => 'balance_delta',
            'type'      => 'currency',
            'currency_code' => $currencyCode,
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
        return null;
    }
   

}
