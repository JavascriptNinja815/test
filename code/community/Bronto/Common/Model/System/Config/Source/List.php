<?php

/**
 * @category Bronto
 * @package  Common
 */
class Bronto_Common_Model_System_Config_Source_List
{
    /**
     * @var array
     */
    protected $_options = array();

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!empty($this->_options)) {
            return $this->_options;
        }

        try {
            if ($api = Mage::helper('bronto_common')->getApi()) {
                /* @var $listObject Bronto_Api_List */
                $listObject = $api->getListObject();
                foreach ($listObject->readAll()->iterate() as $list/* @var $list Bronto_Api_List_Row */) {
                    $this->_options[] = array(
                        'value' => $list->id,
                        'label' => $list->label,
                    );
                }
            }
        } catch (Exception $e) {
            Mage::helper('bronto_common')->writeError('Unable to get Mailing List options: ' . $e->getMessage());
        }

        return $this->_options;
    }
}
