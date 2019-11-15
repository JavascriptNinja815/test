<?php

class TBT_Testsweet_Helper_Data extends Mage_Core_Helper_Abstract 
{
    /**
     * Equivalent to echo
     * @param string $message
     */
    public function printMessage($message)
    {
        $stdout = fopen('php://output', 'w');
        fwrite($stdout, $message);
        fclose($stdout);
    }
}

