<?php

/**
 * Sweet Tooth
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Sweet Tooth SWEET TOOTH POINTS AND REWARDS
 * License, which extends the Open Software License (OSL 3.0).

 * The Open Software License is available at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * By adding to, editing, or in any way modifying this code, Sweet Tooth is
 * not held liable for any inconsistencies or abnormalities in the
 * behaviour of this code.
 * By adding to, editing, or in any way modifying this code, the Licensee
 * terminates any agreement of support offered by Sweet Tooth, outlined in the
 * provided Sweet Tooth License.
 * Upon discovery of modified code in the process of support, the Licensee
 * is still held accountable for any and all billable time Sweet Tooth spent
 * during the support process.
 * Sweet Tooth does not guarantee compatibility with any other framework extension.
 * Sweet Tooth is not responsbile for any inconsistencies or abnormalities in the
 * behaviour of this code if caused by other framework extension.
 * If you did not receive a copy of the license, please send an email to
 * support@sweettoothrewards.com or call 1.855.699.9322, so we can send you a copy
 * immediately.
 *
 * @category   [TBT]
 * @package    [TBT_Rewards]
 * @copyright  Copyright (c) 2014 Sweet Tooth Inc. (http://www.sweettoothrewards.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper for Debugging
 *
 * @category   TBT
 * @package    TBT_Rewards
 * * @author     Sweet Tooth Inc. <support@sweettoothrewards.com>
 */
class TBT_Rewards_Helper_Debug extends Mage_Core_Helper_Abstract
{
    private static $_lastMemoryUsage = null;
    private static $_memoryProfiles = array();

    /**
     * @var array, will store references to objects already printed in recursive
     * calls to $this->toString()
     */
    private $_objectStack;

    public function dd($vo, $with_pre_tags = true) 
    {
        $str = ($vo instanceof Varien_Object)
            ? print_r ( $vo->toArray (), true )
            : $str = print_r ( $vo, true );

        if ($with_pre_tags) {
            $str = "<PRE>" . $str . "</PRE>";
        }
        
        $this->printMessage($str);
    }

    /**
     * @return  a simple backtrace string of the current position (not including
     * any travering into this function)
     * @nelkaake Added on Monday August 20, 2010:
     */
    public function getSimpleBacktrace($offset = 1) {
        $str = "";
        $bt = debug_backtrace ();
        foreach ( $bt as $index => &$btl ) {
            if ($offset > 0) {
                $offset --;
                continue;
            }
            if (isset ( $btl ['file'] ))
                $str .= "> {$btl['file']}";
            if (isset ( $btl ['function'] ))
                $str .= " in function {$btl['function']}(*)";
            if (isset ( $btl ['line'] ))
                $str .= " on line {$btl['line']}.";
            $str .= "\n";
        }
        return $str;
    }

    /**
     * @return  a simple backtrace string of the current position (not including
     * any travering into this function)
     * @nelkaake Added on Monday August 20, 2010:
     */
    public function noticeBacktrace($msg = "") {
        if ($msg != "")
            $msg .= "\n";
        $this->notice ( $msg . $this->getSimpleBacktrace ( 1 ) );
        return $this;
    }

    /**
     * @nelkaake Added on Thursday May 27, 2010: Logging method for ST functions
     * @param mixed $msg
     */
    public function log($msg) 
    {
        Mage::log ( $msg, null, "rewards.log" );
    }
    
    public function printMessage($message)
    {
        $stdout = fopen('php://output', 'w');
        fwrite($stdout, $message);
        fclose($stdout);
    }

    /**
     * Logs an exception into the Sweet TOoth log file
     * @param unknown_type $msg
     */
    public function logException($msg) {
        if (Mage::helper ( 'rewards/developer_config' )->getLogErrorEnabled ()) {
            $this->log ( "[error] " . $msg );
        }
    }

    /**
     * @nelkaake Added on Thursday May 27, 2010: Notice-level logging function
     * @param mixed $msg
     */
    public function notice($msg) {
        if (Mage::helper ( 'rewards/developer_config' )->getLogNoticeEnabled ()) {
            $this->log ( "[notice] " . $msg );
        }
    }

    /**
     * @nelkaake Added on Thursday May 27, 2010: Notice-level logging function
     * @param mixed $msg
     */
    public function warn($msg) {
        if (Mage::helper ( 'rewards/developer_config' )->getLogWarningEnabled ()) {
            $this->log ( "[warning] " . $msg );
        }
    }

    /**
     * @nelkaake Added on Thursday May 27, 2010: Notice-level logging function
     * @param string|exception|mixed $msg
     */
    public function error($msg) {
        if($msg instanceof Exception) {
            $e = $msg;
        } else {
            $e = new Exception($msg);
        }
        $this->logException($e);
        return $this;
    }



    /**
     * @deprecated, use toString() instead.
     * @see TBT_Rewards_Helper_Debug::toString()
     *
     * Returns an array of the inner data of the Varien Object that
     * omitting other varien objects
     * @nelkaake -a 16/11/10:
     */
    public function getPrintableData($obj) {
        if ($obj instanceof Varien_Object) {
            $data = $obj->getData ();
        } elseif (is_array ( $obj )) {
            $data = $obj;
        } else {
            return ( string ) $obj;
        }
        foreach ( $data as $i => $item ) {
            if (is_object ( $item )) {
                $data [$i] = "**CLASS: " . get_class ( $item ) . "**";
            }
            if (is_array ( $item )) {
                $subitem_entry = "**ARRAY[";
                // clean inner array for objects and arrays so we dont print too much
                foreach ( $item as &$subitem ) {
                    if (is_object ( $subitem )) {
                        $subitem = "**CLASS: " . get_class ( $subitem ) . "**";
                    }
                    if (is_array ( $subitem )) {
                        $subitem = "**ARRAY[**hidden**]**";
                    }
                }
                // Add the inner array data separated by commas
                $data [$i] .= implode ( ", ", $item );
                $data [$i] .= "]**";
            }
        }
        return $data;
    }

    /**
     * Will recursively print data in an array or Varien_Object without getting into cyclic references.
     * @param array|Varien_Object|mixed $object
     * @param int $indent, level of recursion we're in
     * @return mixed|string
     */
    function toString($object, $indent = 0)
    {
        if ($indent == 0) {
            $this->_objectStack = array();
        }

        if (in_array($object, $this->_objectStack, true)) {
            if (is_array($object)) {
                $keys = implode(", ", array_keys($object));
                return "Array[{$keys}] - Already Printed.";

            } else if (is_object($object)) {
                $className = get_class($object);
                return "Class <{$className}> - Already Printed.";

            } else {
                return print_r($object, true);
            }
        }

        array_push($this->_objectStack, $object);
        if (is_array($object)) {
            $output = "\n";
            foreach ($object as $index => $value) {
                $output .= "\t" . str_repeat("\t", $indent) . "[{$index}]: ";
                $output .= $this->toString($value, $indent + 1) . "\n";
            }

        } else if ($object instanceof Varien_Object) {
            $output = "Class <" . get_class($object) . ">" . $this->toString($object->getData(), $indent);

        } else {
            $output = print_r($object, true);
        }

        return $output;
    }

    /**
     * Will return current memory usage and a delta against last call to this function
     * @param boolean $showDelta (optional, default: true)
     * @return string formatted
     */
    public function getMemoryUsage($showDelta = true)
    {
        $currentUsage = memory_get_usage();
        $output = "usage: {$currentUsage} b";

        if (!is_null(self::$_lastMemoryUsage) && $showDelta) {
            $delta = $currentUsage - self::$_lastMemoryUsage;
            $output .= "\tdelta: " . $this->formatMemorySize($delta);
        }

        self::$_lastMemoryUsage = $currentUsage;

        return $output;
    }

    /**
     * Will track memory usage given an arbitrary profile number
     * @param int $profileNumber
     * @return string, message with current memory usage
     */
    public function startMemoryProfile($profileNumber)
    {
        $currentUsage = memory_get_usage();
        self::$_memoryProfiles[$profileNumber] = $currentUsage;
        return "Profile #{$profileNumber} started @ {$currentUsage} b";
    }

    /**
     * Will look for a memory profile number that's already started
     * and will show the difference
     * @param int $profileNumber
     * @return string message containing current usage and delta
     */
    public function endMemoryProfile($profileNumber)
    {
        $currentUsage = memory_get_usage();
        if (!isset(self::$_memoryProfiles[$profileNumber])) {
            return "Profile #{$profileNumber} never started.";

        }

        $startingUsage = self::$_memoryProfiles[$profileNumber];
        $delta = $currentUsage - $startingUsage;
        $formatted = $this->formatMemorySize($delta);

        return "Profile #{$profileNumber} ended @ {$currentUsage} b. Total Usage: {$delta}b ({$formatted})";
    }

    /**
     * Returns a formatted string with appropriate memory size units
     * @param number $sizeInBytes. Memory size in bytes
     * @return string formatted value
     */
    protected function formatMemorySize($sizeInBytes)
    {
        $isNegative = ($sizeInBytes < 0);
        $sizeInBytes = abs($sizeInBytes);
        $unit=array('b','kb','mb','gb','tb','pb');
        return ($isNegative? "-":"") . @round($sizeInBytes/pow(1024,($i=floor(log($sizeInBytes,1024)))),2).' '.$unit[$i];
    }

    /**
     * @param string $modelName
     * @param string|int $id
     * @param boolean $wrap, should wrap in HTML <a> tag or not
     * @return string
     */
    public function getLinkToModelData($modelName, $id, $wrap = false)
    {
        $link = Mage::getUrl('rewards/debug_index/viewModelData', array(
            'model' => str_replace('/', ',', $modelName),
            'id'    => $id,
        ));

        if ($wrap) {
            return "<a href='{$link}' target='_blank'>#{$id}</a>";
        }

        return $link;
    }
}
