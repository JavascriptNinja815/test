<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Ordertags
 * @version    1.3.2
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


if (class_exists('MDN_AdvancedStock_Block_Adminhtml_Sales_Order_Grid', false)) {
    class AW_Ordertags_Block_Adminhtml_Sales_Order_GridTmp extends MDN_AdvancedStock_Block_Adminhtml_Sales_Order_Grid
    {
    }

} else {
    class AW_Ordertags_Block_Adminhtml_Sales_Order_GridTmp extends Mage_Adminhtml_Block_Sales_Order_Grid
    {
    }
}

class AW_Ordertags_Block_Adminhtml_Sales_Order_Grid extends AW_Ordertags_Block_Adminhtml_Sales_Order_GridTmp
{
    protected $_exportFlag = false;
    protected function _prepareCollection()
    {
        // KL: Do we have any predefined filter?
        $filter = $this->getParam('filter');
        $filter_data = Mage::helper('adminhtml')->prepareFilterString($filter);
        //Mage::log(__FILE__ . ' ' . __LINE__ . ' ' . print_r($filter_data, true), null, 'kit.log');
        if (count($filter_data) == 0) {
            // KL: Set default filter as store
            $this->setDefaultFilter(array('store_id' => 1, 'created_at' => array('locale' => 'en_US')));
        }

        parent::_prepareCollection();

        $_orderidtotagidTable = Mage::getSingleton('core/resource')->getTableName("ordertags/ordertotag");
        $_tagTable = Mage::getSingleton('core/resource')->getTableName("ordertags/managetags");

        $collection = $this->getCollection();
        $collection->getSelect()
            ->joinleft(
                array('ot' => $_orderidtotagidTable), $this->_getSalesOrdersTableSyn() . '.entity_id = ot.order_id',
                array()
            )
            ->joinleft(array('tag' => $_tagTable), 'ot.tag_id = tag.tag_id')
            ->columns(array('filenames' => new Zend_Db_Expr('CONVERT(GROUP_CONCAT(tag.filename) USING utf8)')))
            ->columns(array('tags' => new Zend_Db_Expr('CONVERT(GROUP_CONCAT(tag.tag_id) USING utf8)')))
            ->group($this->_getSalesOrdersTableSyn() . '.entity_id')
        ;

        $collection->clear();
        $this->setCollection($collection);
        return $this;
    }

    protected function _prepareColumns()
    {
        if (!$this->_exportFlag) {
            $this->addColumn(
                'tag',
                array(
                     'header'                    => Mage::helper('ordertags')->__('Order Tags'),
                     'index'                     => 'tag',
                     'type'                      => 'options',
                     'width'                     => '70px',
                     'options'                   => $this->_returnOptionsList(),
                     'renderer'                  => 'ordertags/adminhtml_sales_order_grid_column_renderer_options',
                     'filter_condition_callback' => array($this, 'filter_tag_callback'),
                     'sortable'                  => false,
                )
            );
        }
        parent::_prepareColumns();
        $this->_exportFlag = false;
    }

    protected function _prepareMassaction()
    {
        parent::_prepareMassaction();

        $tags = Mage::getModel('ordertags/managetags')
            ->getCollection()
            ->setOrder('sort_order', 'ASC')
            ->toOptionArray()
        ;

        $addOrderTagUrl = $this->getUrl(
            'admin_ordertags/adminhtml_managetags/massAddTag', array('_current' => true)
        );
        $this->getMassactionBlock()->addItem(
            'add_ordertag',
            array(
                 'label'      => $this->__('Add Order Tag'),
                 'url'        => $addOrderTagUrl,
                 'additional' => array(
                     'visibility' => array(
                         'name'   => 'tag_id',
                         'type'   => 'select',
                         'class'  => 'required-entry',
                         'label'  => $this->__('Order Tag'),
                         'values' => $tags
                     )
                 )
            )
        );

        $massRemoveTagUrl = $this->getUrl(
            'admin_ordertags/adminhtml_managetags/massRemoveTag', array('_current' => true)
        );
        $this->getMassactionBlock()->addItem(
            'remove_ordertag',
            array(
                 'label'      => $this->__('Remove Order Tag'),
                 'url'        => $massRemoveTagUrl,
                 'additional' => array(
                     'visibility' => array(
                         'name'   => 'tag_id',
                         'type'   => 'select',
                         'class'  => 'required-entry',
                         'label'  => $this->__('Order Tag'),
                         'values' => $tags
                     )
                 )
            )
        );

        $massResetTagsUrl = $this->getUrl(
            'admin_ordertags/adminhtml_managetags/massResetTags', array('_current' => true)
        );
        $this->getMassactionBlock()->addItem(
            'reset_ordertag',
            array(
                 'label' => $this->__('Reset all Order Tags'),
                 'url'   => $massResetTagsUrl,
            )
        );
    }

    protected function filter_tag_callback($collection, $column)
    {
        $val = $column->getFilter()->getValue();

        $_orderidtotagidTable = Mage::getSingleton('core/resource')->getTableName("ordertags/ordertotag");
        if (!@$val) {
            return;
        }
        $cond = array();
        if (@$val) {
            $cond = "ot2.tag_id = $val";
        }
        $collection
            ->getSelect()
            ->joinleft(
                array('ot2' => $_orderidtotagidTable), $this->_getSalesOrdersTableSyn() . '.entity_id = ot2.order_id',
                array()
            )
            ->where($cond)
        ;
    }

    protected function _returnOptionsList()
    {
        $optionsArray = Mage::getModel('ordertags/source_positions')->toOptionArray();
        $optionsList = array();
        foreach ($optionsArray as $value) {
            if ($value['value'] == 'none') {
                continue;
            }
            $optionsList[$value['value']] = $value['label'];
        }
        return $optionsList;
    }

    protected function _getCollection()
    {
        if (preg_match('/^1.4.0/', Mage::getVersion()) || preg_match('/^1.3/', Mage::getVersion())) {
            $collection = Mage::getResourceModel('sales/order_collection')
                ->addAttributeToSelect('*')
                ->joinAttribute('billing_firstname', 'order_address/firstname', 'billing_address_id', null, 'left')
                ->joinAttribute('billing_lastname', 'order_address/lastname', 'billing_address_id', null, 'left')
                ->joinAttribute('shipping_firstname', 'order_address/firstname', 'shipping_address_id', null, 'left')
                ->joinAttribute('shipping_lastname', 'order_address/lastname', 'shipping_address_id', null, 'left')
                ->addExpressionAttributeToSelect(
                    'billing_name',
                    'CONCAT({{billing_firstname}}, " ", {{billing_lastname}})',
                    array('billing_firstname', 'billing_lastname')
                )
                ->addExpressionAttributeToSelect(
                    'shipping_name',
                    'CONCAT({{shipping_firstname}}, " ", {{shipping_lastname}})',
                    array('shipping_firstname', 'shipping_lastname')
                )
            ;
        } else {
            $collection = Mage::getResourceModel('sales/order_grid_collection');
        }
        return $collection;
    }

    protected function _getSalesOrdersTableSyn()
    {
        $syn = 'main_table';

        if (preg_match('/^1.4.0/', Mage::getVersion()) || preg_match('/^1.3/', Mage::getVersion())) {
            $syn = 'e';
        } elseif (preg_match('/^1.4.1/', Mage::getVersion())) {
            $syn = 'main_table';
        }

        return $syn;
    }

    public function getCsvFile()
    {
        $this->_exportFlag = true;
        return parent::getCsvFile();
    }

    public function getExcelFile($sheetName = '')
    {
        $this->_exportFlag = true;
        return parent::getExcelFile($sheetName = '');
    }
}
