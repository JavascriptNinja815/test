<?php
 
class TBT_Reports_Adminhtml_Loyalty_OrderController extends Mage_Adminhtml_Controller_Action
{
    protected $exportFileName = 'magerewards_loyalty_orders';
    
    public function indexAction()
    {
        $helper = Mage::helper('tbtreports');
        $noticeHtml = $helper->__('<span>Consists of orders placed by any customer who:</span>
            <ul class="loyalty-orders-info">
                <li>has ever spent points;</li>
                <li>has earned points from at least 2 orders;</li>
                <li>has earned points from at least one order and one other action (ie: reviews, social media shares);</li>
                <li>has ever transitioned to a new customer tier as a result of reaching a milestone;</li>
                <li>has been referred by someone else.</li>
            </ul>
            <span>Such customers are considered to be active members of your loyalty program.</span>
        ');
        
        Mage::getSingleton('adminhtml/session')->addNotice($noticeHtml);
        
        $this->_title($this->__('Orders from Loyalty Members'));
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_order'));
        $this->renderLayout();
    }
    
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_order_grid')->toHtml()
        );
    }
 
    public function exportCsvAction()
    {
        $fileName = $this->exportFileName . '.csv';
        $grid = $this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_order_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }
 
    public function exportExcelAction()
    {
        $fileName = $this->exportFileName . '.xml';
        $grid = $this->getLayout()->createBlock('tbtreports/adminhtml_loyalty_order_grid');
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }
}