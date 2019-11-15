<?php
class TBT_Rewards_Adminhtml_Points_ImportController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('rewards/customer');
    }

    public function indexAction()
    {
        $message = $this->_getHelper()->__('Imports happen in the background through the Magento CRON.');
        $message .= '&nbsp;<a href="http://support.magerewards.com/article/1533-setting-up-cron-jobs-in-magento">' . $this->_getHelper()->__('Learn More')  .'</a>';
        Mage::getSingleton('adminhtml/session')->addNotice($message);

        $this->loadLayout();
        $this->_addContent(
            $this->getLayout()->createBlock('rewards/adminhtml_points_import')
        )->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('rewards/adminhtml_points_import_grid')->toHtml()
        );
    }

    public function newAction()
    {
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('rewards/adminhtml_points_import_edit'));
        $this->renderLayout();
    }

    public function saveAction()
    {
        try {
            $formData = $this->getRequest()->getPost();
            if (empty($formData) || empty($_FILES)){
                throw new Exception("Not enough data in your submission");
            }

            if (empty($formData['email'])){
                throw new Exception("We need an email address");
            }

            $importer = Mage::getModel('rewards/points_importer');

            // "csvFile" is the upload index in $_FILES
            Mage::getModel('rewards/points_importer')->enqueue('csvFile', $formData['email'], array());

            $message = "Your file has been scheduled for an import. <br/>
                The import will start within a few minutes depending on your CRON configuration. <br/>
                We'll email the results of the import to \"{$formData['email']}\" when done. No need to stay on this page.";

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('rewards')->__($message));
            $this->_redirect('*/*/index');

        } catch (Exception $e){
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('rewards')->__($e->getMessage()));
            $this->_redirect('*/*/new');				
        }
    }
}