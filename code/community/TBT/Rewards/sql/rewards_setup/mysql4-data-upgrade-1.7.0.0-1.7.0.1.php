<?php

/* This script should get a collection of all test transfers, based on the existence
 * of the [DEVELOPER MODE] prefix on the transfers' Comments.  It will then set the is_dev_mode
 * flag of the transfer to true, so that it can be reconciled later. */
$this->attemptQuery("
    UPDATE `{$this->getTable('rewards/transfer')}`
    SET `is_dev_mode` = '1'
    WHERE `comments` LIKE '[DEVELOPER MODE]%';
");

