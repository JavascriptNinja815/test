<?php
class Collinsharper_Beanstreamprofiles_Model_Beanstreamprofiles_CCtype extends Mage_Payment_Model_Source_Cctype
{
    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'DI', 'JCB');
    }
}
