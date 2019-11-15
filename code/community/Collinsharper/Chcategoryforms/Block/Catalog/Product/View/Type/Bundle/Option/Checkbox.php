<?php


/**
 * Bundle option checkbox type renderer
 *
 * @category    Mage
 * @package     Mage_Bundle
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Collinsharper_Chcategoryforms_Block_Catalog_Product_View_Type_Bundle_Option_Checkbox
    extends Mage_Bundle_Block_Catalog_Product_View_Type_Bundle_Option
{
    /**
     * Set template
     *
     * @return void
     */
    protected function _construct()
    {
        $this->setTemplate('chcategoryform/catalog/product/view/type/bundle/option/checkbox.phtml');
    }
}
