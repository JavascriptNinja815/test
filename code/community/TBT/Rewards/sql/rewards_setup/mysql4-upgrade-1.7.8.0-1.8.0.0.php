<?php
/**
 * This only adds an Admin notification about the new Reporting stuff.
 */
$installer = $this;

$installer->startSetup();

$title    = "New Feature - MageRewards Reports!";
$desc     = "We are pleased to let you know that this new version of MageRewards includes a great new feature - "
    . "<strong>MageRewards Reports</strong>. <br/>Get a better insight into your <i>loyalty program</i> effectiveness"
    . " at a glance. Click <i>Read Details</i> for more.";
$url      = "http://support.magerewards.com/article/1677-magerewards-reports";
$severity = Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE;
$installer->createInstallNotice($title, $desc, $url, $severity);

$installer->endSetup();
