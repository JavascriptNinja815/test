<?php


class Collinsharper_Chcustom_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{

	public function printlabelsAction()
    {

        $id = $this->getRequest()->getParam('id', false);
        if(!$id) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalog')->__('Must specify rule Id.'));
        }
        Mage::register('current_promo_quote_rule', Mage::getModel('salesrule/rule'));
        Mage::registry('current_promo_quote_rule')->load($id);
        if(!Mage::registry('current_promo_quote_rule') || !Mage::registry('current_promo_quote_rule')->getId()) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalog')->__('Could not load rule.'));
        }

        mage::log(__METHOD__ . __LINE__);
        $fileName = date('Y-m-d') . '_Rule_' .  preg_replace('/[^a-z0-9]+/i','_', Mage::registry('current_promo_quote_rule')->getName()) . '.odt';
        $content = $this->_generatePdfData($id);
        $this->_prepareDownloadResponse($fileName, $content);

	}


    private function _generatePdfData($id)
    {

        $priceRule = Mage::getModel('salesrule/rule')->load($id);
        $collection = Mage::getResourceModel('salesrule/coupon_collection')
            ->addRuleToFilter($priceRule)
            ->addGeneratedCouponsFilter();

        /**
         * open the ODT template - ex expect each cell has a {X} in it.
         * substring the document for the following
         *  <office:body> ... </office:body>
         * start page end
         */

        $docStart = '<office:body>';
        $docEnd = '</office:body>';
        $pageBreak = '<text:soft-page-break/>';
        $explodeMark = '{X}';
        // This is specific to the 5167 avery format
        $labelsPerPage = 80;
        $contentCount = $collection->count();
        $dir = BP . DS . 'var' . DS . 'tmp' . DS . rand(22,9999);

        $outputFile = $dir . DS . "Myreel-Retail-tmp.odt";

        mkdir($dir,0775, true);
        @unlink($outputFile);

        $cmd="cd {$dir}; unzip -q " . BP . DS . "shell" . DS . "avery" . DS . "Original-ODT-Avery5167.odt";
        $out = passthru($cmd);
        $content = file_get_contents($dir . '/' . 'content.xml');

        $startPos = strpos($content, $docStart);
        $startEnd =  $startPos + strlen($docStart);
        $startData = substr($content, 0, $startEnd);
        $endPos = strpos($content, $docEnd);
        $contentData = substr($content, $startEnd, $endPos - $startEnd);
        $endData = substr($content, $endPos);


        $pages = ceil($contentCount/$labelsPerPage);
        $newContent = '';
        for($i=0;$i<$pages;$i++) {
            $newContent .= $contentData;
            if($i+1 != $pages) {
                $newContent .= $pageBreak;
            }
        }

        $parsedPages = explode($explodeMark, $newContent);
        $newContent = $startData;

        foreach($collection as $item) {
            $newContent .= array_shift($parsedPages).$item->getCode();
        }

        $newContent .= implode("", $parsedPages) . $endData;

        file_put_contents($dir . DS . 'content.xml', $newContent);

        $cmd = "cd {$dir}; zip -qr {$outputFile} *";
        $out = passthru($cmd);


        $data = file_get_contents($outputFile);

        $cmd = "rm -rf  {$dir}";
        $out = passthru($cmd);
        $priceRule->setLabelsPrinted(1);
        $priceRule->save();
        return $data;
    }
}

