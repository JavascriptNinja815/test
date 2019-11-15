<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Giftcard
 * @version    1.0.8
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


$installer = $this;
$installer->startSetup();
$isAwGcTypeUsedInProductListing = $installer->getAttribute('catalog_product', 'aw_gc_type', 'used_in_product_listing');
if ($isAwGcTypeUsedInProductListing === '0' || $isAwGcTypeUsedInProductListing === 0) {
    $installer->updateAttribute('catalog_product', 'aw_gc_type', 'used_in_product_listing', 1);
}

$installer->run( "

insert into aw_giftcard select  giftcardaccount_id, code, status, date_created, date_expires, website_id, balance, state from enterprise_giftcardaccount; 

insert into aw_giftcard_history select null, giftcardaccount_id, now(), 1, balance, balance, 'a:2:{s:12:\"message_type\";i:0;s:12:\"message_data\";s:7:\"chadmin\";}' from enterprise_giftcardaccount;

");
$installer->endSetup();
