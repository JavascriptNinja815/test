<?php

class TBT_Testsweet_Model_Test_Suite_Magento_Filesystem extends TBT_Testsweet_Model_Test_Suite_Abstract {

    public function getRequireTestsweetVersion() {
        return '1.0.0.0';
    }

    public function getSubject() {
        return $this->__('Magento - Filesystem');
    }

    public function getDescription() {
        return $this->__('Check that Magento has read/write on filesystem.');
    }

    protected function generateSummary() {

        $check_writeable = array(
            Mage::getBaseDir('tmp'),
            Mage::getBaseDir('log'),
            Mage::getBaseDir('etc'),
            Mage::getBaseDir('upload'),
        );

        foreach ($check_writeable as $path) {
            if (is_readable($path) && is_writable($path)) {
                $this->addPass($this->__('Seems accessible %s', $path));
            } else {
                $this->addFail($this->__('Check filesystem access on %s', $path));
            }
        }

        $target = Mage::getBaseDir('base');
        $directory = new RecursiveDirectoryIterator($target);
        $iterator = new RecursiveIteratorIterator($directory);

        $not_readable_count = 0;
        foreach ($iterator as $fullpath => $file) {
            if (strpos($fullpath, '/var/cache/') !== false) {
                continue;
            }

            if (!is_readable($fullpath)) {
                $this->addNotice($this->__('Not accessible %s', $fullpath));
                $not_readable_count++;
            }
            
            if ($not_readable_count > 50) {
                $this->addWarning($this->__('More than 50 none accessible files... skipping test.'));
                break;
            }
        }
    }
}
