<?php

/**
 * Interface TBT_Reports_Block_Feed_Interface
 * Used to set a common interface for feed item blocks
 */
interface TBT_Reports_Block_Adminhtml_Dashboard_FeedsArea_Tabs_Feed_Item_Interface
{

    /**
     * Return the id of the item
     * @return mixed
     */
    public function getId();

    /**
     * Primary source of the feed item
     *
     * @param mixed $object
     * @return $this
     */
    public function setItemObject($object);

    /**
     * HTML Text for the feed, including links and HTML tags.
     * @return string
     */
    public function getMessage();

    /**
     * Array of classes to apply to this feed item
     * @return array
     */
    public function getClasses();

    /**
     * String for parse-able timestamp
     * @return string
     */
    public function getTimestamp();
}