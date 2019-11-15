<?php


class Collinsharper_Chcategoryforms_Model_Source_Categorylist extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{

    public function toOptionArray($addEmpty = true)
    {
        $tree = Mage::getResourceModel('catalog/category_tree');

        $collection = Mage::getResourceModel('catalog/category_collection');

        $collection->addAttributeToSelect('name')
          //  ->addRootLevelFilter()
            ->load();

        $options = array();

        if ($addEmpty) {
            $options[] = array(
                'label' => Mage::helper('adminhtml')->__('-- Select a category for form control --'),
                'value' => ''
            );
        }
        foreach ($collection as $category) {
            $level = $category->getLevel();
            if($category->getLevel() <= 2) {
                $options[] = array(
                    'label' => str_repeat('-', $category->getLevel()) . ' ' . $category->getName(),
                    'value' => $category->getId()
                );
            }
        }

        return $options;
    }
	public function getAllOptions()
	{
		return $this->toOptionArray(true);
	}
}


