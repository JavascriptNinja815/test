<?php

class TBT_Common_Helper_Array extends Mage_Core_Helper_Abstract
{
    /**
     * http://stackoverflow.com/q/173400/130691
     * @param unknown_type $array
     * @return boolean
     */
    public function isAssoc($array)
    {
        return ($array !== array_values($array));
    }

    public function flatten($array)
    {
        $data = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $data = array_merge($data, $this->flatten($value));
            } else {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
