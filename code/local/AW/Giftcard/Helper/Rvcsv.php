<?php

class AW_Giftcard_Helper_Rvcsv extends Mage_Core_Helper_Abstract
{
    protected $_list;

    public function setList($arrGcIds)
    {
	$collection = Mage::getModel('aw_giftcard/giftcard')->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $arrGcIds));

	$this->_list = $collection;
    }

    protected function _getCsvHeaders($giftcards)
    {
	$giftcard = current($giftcards);
        //$headers = array_keys($giftcard->getData());
        $headers = array('code', 'code');
 
        return $headers;
    }

    public function generateGcList()
    {
        if (!is_null($this->_list)) {
            $items = $this->_list->getItems();
            if (count($items) > 0) {
 
                $io = new Varien_Io_File();
                $path = Mage::getBaseDir('var') . DS . 'export' . DS;
                $name = md5(microtime());
                $file = $path . DS . $name . '.csv';
                $io->setAllowCreateFolders(true);
                $io->open(array('path' => $path));
                $io->streamOpen($file, 'w+');
                $io->streamLock(true);
 
                $io->streamWriteCsv($this->_getCsvHeaders($items));
		$tempCodes = array();
		$numCodes = count($items);
		$idx = 0;
                foreach ($items as $giftcard) {
		    $idx++;
	            $tempCodes[] = $giftcard->getCode();
		    if(count($tempCodes) == 2 || $idx == $numCodes) {
			$io->streamWriteCsv($tempCodes);
			$tempCodes = array();
		    }
                    //$io->streamWriteCsv($giftcard->getData());
                }
 
                return array(
                    'type'  => 'filename',
                    'value' => $file,
                    'rm'    => true // can delete file after use
                );
            }
        }
    }
}
