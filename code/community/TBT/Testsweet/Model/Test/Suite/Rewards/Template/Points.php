<?php

class TBT_Testsweet_Model_Test_Suite_Rewards_Template_Points extends TBT_Testsweet_Model_Test_Suite_Abstract
{
    public function getRequireTestsweetVersion()
    {
        return '1.0.0.0';
    }

    public function getSubject()
    {
        return $this->__('Check template files');
    }

    public function getDescription()
    {
        return $this->__('Check required template point files are found.');
    }

    protected function generateSummary()
    {
        $paths = array(
            Mage::getBaseDir('design') . '/frontend/base/default/layout/rewards.xml',
            Mage::getBaseDir('design') . '/frontend/base/default/template/rewards/points.phtml',
        );

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $this->addPass($this->__('Found: %s', $path));
            } else {
                $this->addFail($this->__('Missing %s', $path));
            }
        }
    }
}

