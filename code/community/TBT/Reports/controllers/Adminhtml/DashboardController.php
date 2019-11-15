<?php

class TBT_Reports_Adminhtml_DashboardController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return true;
    }
    
    /**
     * Sweet Tooth Dashboard Page
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Summary ajax call will render it's full block
     * @return $this
     */
    public function summaryAjaxAction()
    {
        $result = $this->getLayout()->createBlock('tbtreports/adminhtml_dashboard_widget_summary')->getAjaxHtml();

        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        $this->getResponse()->setBody(Zend_Json::encode($result));

        return $this;
    }
}