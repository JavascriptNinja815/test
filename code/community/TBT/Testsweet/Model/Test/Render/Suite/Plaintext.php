<?php

class TBT_Testsweet_Model_Test_Render_Suite_Plaintext extends TBT_Testsweet_Model_Test_Render_Suite_Abstract
{
    public function __construct() 
    {
        $render = new TBT_Testsweet_Model_Test_Render_Report_Plaintext();
        $this->setReportRender($render);
    }

    public function render() 
    {
        $suite = $this->getSuite();
        $suite->getSummary();

        if (!$this->isFiltered($suite->getStatus())) {
            $helper = Mage::helper('testsweet');
            $message = "\n\n{$suite->getSubject()}\n";
            $message .= "----------------------------------------------------------------\n";
            $message .= "{$suite->getDescription()}\n";
            $helper->printMessage($message);

            foreach ($suite->getSummary() as $report) {
                if (!$this->isFiltered($report->getStatus())) {
                    $this->getReportRender()->setReport($report)->render();
                }
            }

            $message = "  |\n";
            $message .= "[{$suite->getStatusString()}]\n";
            $helper->printMessage($message);
            
            if ($suite->getException()) {
                $helper->printMessage("== {$this->__("Error")} ==\n{$suite->getException()->getMessage()}");
            }
        }
    }
}

