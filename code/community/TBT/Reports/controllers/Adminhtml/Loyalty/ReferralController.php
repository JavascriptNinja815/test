<?php
 
class TBT_Reports_Adminhtml_Loyalty_ReferralController extends Mage_Adminhtml_Controller_Action
{
    protected $exportFileName = 'magerewards_referral_orders';
    
    public function indexAction()
    {
        $helper = Mage::helper('tbtreports');
        $noticeHtml = $helper->__('<span>Consists of orders placed by referred customers.</span>');
        
        Mage::getSingleton('adminhtml/session')->addNotice($noticeHtml);
        
        $this->_title($this->__('Orders from Referred Customers'));
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_referral'));
        $this->renderLayout();
    }
 
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_referral_grid')->toHtml()
        );
    }
 
    public function exportCsvAction()
    {
        $fileName = $this->exportFileName . '.csv';
        $grid = $this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_referral_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }
 
    public function exportExcelAction()
    {
        $fileName = $this->exportFileName . '.xml';
        $grid = $this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_referral_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
}

