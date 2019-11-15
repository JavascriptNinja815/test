<?php
class Collinsharper_FraudBlock_Model_Source_Blocktype
{

	const BLOCK_SESSION = 01;
	const BLOCK_IP = 02;
	const BLOCK_DATA = 03;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
		
            array('value' => self::BLOCK_SESSION, 'label'=>Mage::helper('adminhtml')->__('Session')),
            array('value' => self::BLOCK_IP, 'label'=>Mage::helper('adminhtml')->__('IP')),
            array('value' => self::BLOCK_DATA, 'label'=>Mage::helper('adminhtml')->__('Permanent Block by Customer Data')),
        );
    }

}
