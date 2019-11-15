<?php

class TBT_Testsweet_Model_Test_Render_Report_Html extends TBT_Testsweet_Model_Test_Render_Report_Abstract 
{
    public function render() 
    {
        $report = $this->getReport();
        $content = "<ol>";
        $content .= "<li>";
        $content .= "<b class='{$report->getStatusString()}'>{$report->getStatusString()}</b>";
        $content .= "<pre>{$report->getSubject()}</pre>";
        $content .= "<pre>{$report->getDescription()}</pre>";
        $content .= "</li>";
        $content .= "</ol>";
        
        Mage::helper('testsweet')->printMessage($content);
    }
}
