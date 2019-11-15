<?php
/**
 * Created by JetBrains PhpStorm.
 * User: KL
 */
$installer = new Mage_Customer_Model_Entity_Setup('core_setup');

$installer->startSetup();
  
try {
   $installer->run("
        ALTER TABLE `{$this->getTable('enterprise_giftcardaccount')}` add COLUMN `notes` varchar(1000);
    ");
} catch (Exception $e) {}
try {

 $installer->run("
        ALTER TABLE `{$this->getTable('salesrule_coupon')}` add COLUMN `notes` varchar(1000);
    ");

} catch (Exception $e) {}
try {
 $installer->run("
        ALTER TABLE `{$this->getTable('salesrule_coupon')}` add COLUMN `labels_printed` tinyint(1) default '0';
    ");

} catch (Exception $e) {}
try {
 $installer->run("
        ALTER TABLE `{$this->getTable('salesrule_coupon')}` add COLUMN `myretail` tinyint(1) default '0';
    ");

} catch (Exception $e) {}
try {
// we have to index order coupon so that we can search by it later
 $installer->run("
        ALTER TABLE `{$this->getTable('sales_flat_order')}` add index(coupon_code);
    ");

} catch (Exception $e) {}
try {
// we have to index order coupon so that we can search by it later
 $installer->run("
        ALTER TABLE `{$this->getTable('sales_flat_order_item')}` add index(sku);
    ");

} catch (Exception $e) {}
try {
 $installer->run("
        INSERT ignore INTO `{$this->getTable('cms_block')}`` VALUES (null,'My Retail Cart Message Success','cart_message_myretail_success','<!-- this message is left blank intentionally -->',now(),now(),1),(null,'My Retail Cart Message Error items','cart_message_myretail_error','<!-- this message will be show on the cart when a customer adds a my retail coupon code but has multiple items in their cart; which we could not automatically decide which to switch out. \r\nThere should be a link in this message __LINK__ that will do something -->\r\n\r\nYour order qualities for free shipping and a free reel, however the products you have in your cart do not qualify; click here to have your free reel added to the cart with the existing reel in your cart.',now(),now(),1);
    ");

} catch (Exception $e) {}

$installer->endSetup();
