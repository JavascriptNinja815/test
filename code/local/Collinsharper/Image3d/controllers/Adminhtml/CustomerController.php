<?php
require_once BP . '/app/code/core/Mage/Adminhtml/controllers/CustomerController.php';



class Collinsharper_Image3d_Adminhtml_CustomerController extends Mage_Adminhtml_CustomerController
{
    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'customers.csv';
        $block = $this->getLayout()->createBlock('adminhtml/customer_grid');
        Mage::dispatchEvent('core_block_abstract_to_html_before', array('block' => $block));
        $content = $block->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export customer grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName   = 'customers.xml';
        $block = $this->getLayout()->createBlock('adminhtml/customer_grid');
        Mage::dispatchEvent('core_block_abstract_to_html_before', array('block' => $block));
        $content = $block->getCsvFile();

        $this->_prepareDownloadResponse($fileName, $content);
    }

}
