<?php

class Collinsharper_Image3d_Model_System_Config_Source_Ordertags
{
    public function toOptionArray()
    {
        $array = array();

        $data = $this->toArray();
        foreach($data as $v => $l) {
            $array[] =  array('value' => $v, 'label' => $l);
        }

        return $array;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();
	return $array;
        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = " select tag_id, name from aw_ordertags_tags order by name ";
        $rows = $read->fetchAll($sql);
        foreach($rows as $r) {
            $array[$r['tag_id']] = $r['name'];
        }
        return $array;
    }

}
