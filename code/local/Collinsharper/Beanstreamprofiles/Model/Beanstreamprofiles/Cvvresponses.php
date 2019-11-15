<?php
class Collinsharper_Beanstreamprofiles_Model_Beanstreamprofiles_Cvvresponses extends Mage_Payment_Model_Source_Cctype
{
 
public function toOptionArray()
	{
		return array(
		array(
				'value' => '1', 
				'label' => '1 - CVD Match'
			),
		array(
				'value' => '2', 
				'label' => '2 - CVD Mismatch'
			),
		array(
				'value' => '3', 
				'label' => '3 - CVD Not Verified '
			),
		array(
				'value' => '4', 
				'label' => '4 - CVD Should have been present'
			),
		array(
				'value' => '5', 
				'label' => '5 - CVD Issuer unable to process request '
			),
		array(
				'value' => '6', 
				'label' => '6 - CVD Not Provided'
			),
		);
	}

	}
