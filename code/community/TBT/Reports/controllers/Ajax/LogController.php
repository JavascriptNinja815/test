<?php

require_once(Mage::getModuleDir('controllers', 'TBT_Reports') . DS . 'AjaxController.php');
class TBT_Reports_Ajax_LogController extends TBT_Reports_AjaxController
{
    /**
     * Provides protected access to tbtreports log file for remote debugging purposes
     * @return $this
     */
    public function tbtreportsAction()
    {
        $logDir = Mage::getBaseDir('log');
        $logFile = $logDir . DS . 'tbtreports.log';
        $helper = Mage::helper('rewards/debug');
        
        if (file_exists($logFile)) {
            $this->getResponse()->setHeader('Content-Type', 'text/plain', true);
            
            $helper->printMessage('<pre>');
            readfile($logFile);
            $helper->printMessage('</pre>');
        }
    }
}

