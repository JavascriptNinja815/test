<?php

ini_set('memory_limit', '1024M');

class Collinsharper_Image3d_Adminhtml_Report_Sales_Order_Shipment_ItemController extends Mage_Adminhtml_Controller_Action
{
    protected $_gridContainerBlockName = 'chimage3d/adminhtml_report_sales_order_shipment_item';
    protected $_title = 'Shipped Items';
    protected $_exportFileName = 'shipped-items';

    public function indexAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('report/chimage3d_custom')
            ->_title($this->__('Custom Reports'))
            ->_title($this->__($this->_title));
        $this->_addContent($this->getLayout()->createBlock($this->_gridContainerBlockName));
        $this->renderLayout();
    }

    /**
     * Export order grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = "{$this->_exportFileName}.csv";
        $grid       =  $this->getLayout()->createBlock($this->_gridContainerBlockName . '_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsv());
    }

    /**
     *  Export order grid to Excel XML format
     */
    public function exportExcelAction()
    {
        $fileName   = "{$this->_exportFileName}.xml";
        $grid       = $this->getLayout()->createBlock($this->_gridContainerBlockName . '_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcel($fileName));
    }

}
