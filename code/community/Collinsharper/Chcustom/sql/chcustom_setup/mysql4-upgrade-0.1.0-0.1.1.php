<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kl
 */
$installer = $this;

$installer->startSetup();

// Create Static Block is_available_for_store_pickup
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
$blockContent = <<<EOF
<script language="javascript">
//<![CDATA[
    alert('Image3D is not liable for the duties and taxes on your package\n\nUPS and USPS do not guarantee transit times');
//]]>
</script>
EOF;

$staticBlock = array(
    'title' => 'Checkout Shipping International Message',
    'identifier' => 'checkout_shipping_international_message',
    'content' => $blockContent,
    'is_active' => 1,
    'stores' => array(1)
);

$collection = Mage::getModel('cms/block')->getCollection()->addFieldToFilter('identifier', $staticBlock['identifier']);
$block_id = $collection->getFirstItem()->getId();
$block = Mage::getModel('cms/block')->load($block_id);
$block->setId($block_id)->delete();
Mage::getModel('cms/block')->setData($staticBlock)->save();

$installer->endSetup();