<?php
class Collinsharper_Beanpro_Model_Beanpro_CCtype extends Mage_Payment_Model_Source_Cctype
{
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'DI', 'JCB');
    }
}
