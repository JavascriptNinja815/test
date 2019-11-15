<?php

class TBT_Testsweet_Model_Test_Suite_Magento_Module_Conflict extends TBT_Testsweet_Model_Test_Suite_Abstract {

    public function getRequireTestsweetVersion()
    {
        return '1.0.0.0';
    }

    public function getSubject() 
    {
        return $this->__('Magento - Modules - Check rewrite conflicts');
    }

    public function getDescription()
    {
        return $this->__('Check modules for rewrite conflicts.');
    }

    protected function generateSummary()
    {
        $conflicts = $this->findModuleConflicts();

        if (empty($conflicts)) {
            $this->addPass($this->__("No extension conflict found."));
        } else {
            $this->addWarning($this->__("Conflicts found"));
            $this->addNotice($this->__("More help can be found here: http://support.magerewards.com/article/1694-conflicting-extensions") ); 
        }
    }

    /**
     * Detect module conflicts
     * @return @array 
     */
    protected function findModuleConflicts() 
    {
        $conflicts = array();
        $types = array('block', 'helper', 'model');
        $modules = Mage::getConfig()->getNode('modules')->children();
        
        foreach ($types as $type) {
            foreach ($modules as $moduleName => $moduleDetails) {
                if (!$moduleDetails->is('active')) {
                    continue;
                }
                
                $configFile = Mage::getConfig()->getModuleDir('etc', $moduleName) . DS . 'config.xml';
                $moduleConfig = Mage::getModel('core/config_base');
                $moduleConfig->loadFile($configFile);
                $baseConfig = Mage::getModel('core/config_base');
                $baseConfig->loadString('<config/>');
                $baseConfig->extend($moduleConfig, true);

                $typeNode = $baseConfig->getNode()->global->{$type.'s'};
                if (!$typeNode) {
                    continue;
                }

                foreach ($typeNode->children() as $targetModule => $elements) {
                    $rewrites = $elements->rewrite;
                    
                    if ($rewrites) {
                        $config = Mage::getConfig()->getNode()->global->{$type.'s'}->{$targetModule};
                        
                        foreach ($rewrites->children() as $currentClass => $finalClass) {
                            $finalClass = reset($finalClass);
                            $rewrittenClass = (string) $config->rewrite->$currentClass;
                            
                            if ($finalClass == $rewrittenClass) {
                                /* No conflict */
                                continue;
                            } elseif (is_subclass_of($rewrittenClass, $finalClass)) {
                                /* Fixed conflict */
                                $this->addNotice("[fixed conflict] - {$finalClass}");
                            } else {
                                /* Outstanding Conflict */
                                $this->addFail("[conflict found] - {$finalClass}");
                                $conflicts[] = $finalClass;
                            }
                        }
                    }
                }
            }
        }

        return $conflicts;
    }
}

