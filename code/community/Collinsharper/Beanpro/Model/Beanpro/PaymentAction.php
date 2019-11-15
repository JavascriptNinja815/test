<?php

class Collinsharper_Beanpro_Model_Beanpro_PaymentAction
{
	public function toOptionArray()
	{
		return array(
			array(
				'value' => Collinsharper_Beanpro_Model_PaymentMethod::ACTION_AUTHORIZE_CAPTURE,
				'label' => Mage::helper('paygate')->__('Authorise and Capture')
			),
			array(
				'value' => Collinsharper_Beanpro_Model_PaymentMethod::ACTION_AUTHORIZE,
				'label' => Mage::helper('paygate')->__('Authorise')
			)
        );
	}
}
