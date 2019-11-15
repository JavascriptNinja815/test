<?php

class TBT_Testsweet_Model_Test_Render_Suite_Html extends TBT_Testsweet_Model_Test_Render_Suite_Abstract
{
    public function __construct() 
    {
        $render = new TBT_Testsweet_Model_Test_Render_Report_Html();
        $this->setReportRender($render);
    }

    public function render() 
    {
        $suite = $this->getSuite();
        $suite->getSummary();

        if (!$this->isFiltered($suite->getStatus())) {
            $helper = Mage::helper('testsweet');
            $message = "<h2>{$suite->getSubject()}</h2>";
            $message .= "<p>{$suite->getDescription()}</p>";
            $message .= "<ol>";
            $helper->printMessage($message);
            
            foreach ($suite->getSummary() as $report) {
                if (!$this->isFiltered($report->getStatus())) {
                    $this->getReportRender()->setReport($report)->render();
                }
            }
            
            $message = "</ol>";
            $message .= "<li><b>{$suite->getStatusString()}</b></li>";
            $helper->printMessage($message);
            
            if ($suite->getException()) {
                $helper->printMessage("<b>{$this->__("Error")}</b><br/><pre>{$suite->getException()->getMessage()}</pre>");
            }
        }
    }
}

