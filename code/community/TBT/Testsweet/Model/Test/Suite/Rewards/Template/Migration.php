<?php

class TBT_Testsweet_Model_Test_Suite_Rewards_Template_Migration extends TBT_Testsweet_Model_Test_Suite_Abstract
{
    public function getRequireTestsweetVersion()
    {
        return '1.0.0.0';
    }

    public function getSubject()
    {
        return $this->__('Template file migration');
    }

    public function getDescription()
    {
        return $this->__('Check required template files are found in the correct locations.');
    }

    protected function generateSummary()
    {
        $basePaths = array(
            Mage::getBaseDir('design') . '/frontend/base/default/template/rewards',
            Mage::getBaseDir('design') . '/frontend/base/default/layout/rewards.xml',
        );
        
        $hasRewardsFilesInBase = false;
        foreach ($basePaths as $path) {
            if (file_exists($path)) {
                $hasRewardsFilesInBase = true;
            }
        }
        
        $defaultPaths = array(
            Mage::getBaseDir('design') . '/frontend/default/default/template/rewards', 
            Mage::getBaseDir('design') . '/frontend/default/default/layout/rewards.xml'
        );
        
        $hasRewardsFilesInDefault = false;
        foreach ($defaultPaths as $path) {
            if (file_exists($path)) {
                $hasRewardsFilesInDefault = true;
            }
        }

        if ($hasRewardsFilesInDefault) {
            $this->addFail($this->__('MageRewards template files should be in base/default not default/default'), $this->__('Help can be found here: http://support.magerewards.com/category/1528-category'));
        } elseif ($hasRewardsFilesInBase && !$hasRewardsFilesInDefault) {
            $this->addPass($this->__('MageRewards template files seem to be correctly located in base/default.'));
        }
    }
}

