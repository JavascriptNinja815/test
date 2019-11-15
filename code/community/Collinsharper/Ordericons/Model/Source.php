<?php

class Collinsharper_Ordericons_Model_Source extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getOptionsList()
    {
        $x = Mage::getModel('chordericons/chicons')->getCollection();
        $options = array();
        foreach($x as $icon) {
            $options[$icon->getId()] =  $icon->getName();
        }
        return $options;
    }

    public function getAllOptions()
    {
        $parts = $this->getOptionsList();
        foreach($parts as $v => $l) {
            $array = array('label' => $l, 'value' => $v);
            $options[] =  $array;
        }
        return $options;
    }


    public function getTextList()
    {
        $parts = $this->getOptionsList();
        foreach($parts as $v => $l) {
            $options[$l] = $l;
        }
        return $options;
    }


}

