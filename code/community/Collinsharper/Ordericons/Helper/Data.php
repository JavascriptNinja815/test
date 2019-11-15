<?php

class Collinsharper_Ordericons_Helper_Data extends Mage_Core_Helper_Abstract
{

    const MEDIA_PATH = 'chordericons';


    public function getMediaPath()
    {
        return Mage::getBaseDir('media') . DS . 'chordericons' . DS ;
    }

    public function getMediaUrl($file = '')
    {
        return Mage::getBaseUrl('media') . DS . 'chordericons' . DS  . $file;
    }



    public function getOrderIcons($order)
    {
        $images = array();
        $rowIcons = explode(",", $order->getData('ch_order_icons'));
        // TODO we should cache these or goup_concat the query so  we do not need this here.
        $x = Mage::getModel('chordericons/chicons')->getCollection();
        $x->addFieldToFilter('icon_id', array('in' => $rowIcons));

        $mediaPath = ($this->getMediaUrl() . DS) ;

        foreach($x as $row) {
            $images[$row->getData('name')] = $mediaPath . $row->getData('image');
        }

        return $images;
    }

    public function setOrderIcons($orderId, $orderIcons, $comment = '')
    {
	$data = array();
            foreach($orderIcons as $icon) {
                $data[] = array(
                    'order_id' => $orderId,
                    'icon_id' => $icon,
                    'comment' => $comment,
                );
            }

            if(count($data)) {
                $resource = Mage::getSingleton('core/resource');
                $write = $resource->getConnection('core_write');
                $tableName = $resource->getTablename('ch_sales_flat_order_icons');
                $write->insertMultiple($tableName, $data);
            }
	
    }
}
