<?php
class Collinsharper_Beanpro_Model_Beanpro_Avsresponses extends Mage_Payment_Model_Source_Cctype
{
	public function toOptionArray()
	{
		return array(
			 array(     'value' =>'0',      'label' => '0 - Addr Verification not performed.'    ),
			 array(     'value' =>'5',      'label' => '5 - Invalid AVS Response.'    ),
			 array(     'value' =>'9',      'label' => '9 - Addr Verification Data contains edit error.'    ),
			 array(     'value' =>'A',      'label' => 'A - Street addr match, Post/ZIP doesnt.'    ),
			 array(     'value' =>'B',      'label' => 'B - Street addr match, Post/ZIP not verified.'    ),
			 array(     'value' =>'C',      'label' => 'C - Street addr and Post/ZIP not verified.'    ),
			 array(     'value' =>'D',      'label' => 'D - Street addr and Post/ZIP match.'    ),
			 array(     'value' =>'E',      'label' => 'E - Transaction ineligible.'    ),
			 array(     'value' =>'G',      'label' => 'G - Non AVS participant. Info not verified.'    ),
			 array(     'value' =>'I',      'label' => 'I - Addr info not verified for intl transaction.'    ),
			 array(     'value' =>'M',      'label' => 'M - Street addr and Post/ZIP match. '    ),
			 array(     'value' =>'N',      'label' => 'N - Street addr and Post/ZIP do not match. '    ),
			 array(     'value' =>'P',      'label' => 'P - Post/ZIP match. Street addr not verified. '    ),
			 array(     'value' =>'R',      'label' => 'R - System unavailable or timeout.'    ),
			 array(     'value' =>'S',      'label' => 'S - AVS not supported at this time.'    ),
			 array(     'value' =>'U',      'label' => 'U - Addr information is unavailable.'    ),
			 array(     'value' =>'W',      'label' => 'W - Post/ZIP match, street addr doesnt. '    ),
			 array(     'value' =>'X',      'label' => 'X - Street addr and Post/ZIP match.'    ),
			 array(     'value' =>'Y',      'label' => 'Y - Street addr and Post/ZIP match.'    ),
			 array(     'value' =>'Z',      'label' => 'Z - Post/ZIP match, street addr doesnt. '    ),


		);
	}
}
