<?php

ini_set('memory_limit', '1024M');

class Collinsharper_Image3d_Adminhtml_Report_GiftCardController extends Mage_Adminhtml_Controller_Action
{
    protected $_gridContainerBlockName = 'chimage3d/adminhtml_report_giftCard';
    protected $_title = 'Gift Cards';
    protected $_exportFileName = 'gift-cards';

    public function indexAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('report/chimage3d_custom')
            ->_title($this->__('Custom Reports'), $this->__('Custom Reports'))
            ->_title($this->__($this->_title), $this->__($this->_title));
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
