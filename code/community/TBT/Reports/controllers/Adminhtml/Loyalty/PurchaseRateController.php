<?php
 
class TBT_Reports_Adminhtml_Loyalty_PurchaseRateController extends Mage_Adminhtml_Controller_Action
{
    protected $exportFileName = 'magerewards_loyalty_purchase_rate';
    
    public function indexAction()
    {
        $this->_title($this->__('Frequency of Purchase'));
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_purchaseRate'));
        $this->renderLayout();
    }
    
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_purchaseRate_grid')->toHtml()
        );
    }
 
    public function exportCsvAction()
    {
        $fileName = $this->exportFileName . '.csv';
        $grid = $this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_purchaseRate_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }
 
    public function exportExcelAction()
    {
        $fileName = $this->exportFileName . '.xml';
        $grid = $this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_purchaseRate_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
}