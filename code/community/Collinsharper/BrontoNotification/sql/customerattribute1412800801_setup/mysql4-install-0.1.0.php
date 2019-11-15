<?php
$installer = $this;
$installer->startSetup();


$attributes = array(
    'has_reels' => array(
        'label' =>'Has Reels',
        'type' =>'int',
        'input' =>'select',
        //catalog/product_attribute_backend_startdate_specialprice
        'source_model' =>'eav/entity_attribute_source_boolean',
        'backend_model' =>'catalog/product_attribute_backend_boolean',
    ),

    'has_unfinished_reel' => array(
        'label' =>'Unfinished Reels Date',
        'type' =>'datetime',
        'input' =>'date',
        'source_model' =>'',
        'backend_model' =>'catalog/product_attribute_backend_startdate_specialprice',
    ),
    'unordered_completed_reel' =>  array(
        'label' =>'Unordered Completed Reels Date',
        'type' =>'datetime',
        'input' =>'date',
        //
        'source_model' =>'',
        'backend_model' =>'catalog/product_attribute_backend_startdate_specialprice',
    ),
);

foreach($attributes as $code => $data) {
    $installer->addAttribute("customer", $code, array(
        "type" => $data['type'],
        "backend" => $data['backend_model'],
        "label" => $data['label'],
        "input" => $data['input'],
        "source" => $data['source_model'],
        "visible" => false,
        "required" => false,
        "default" => "",
        "frontend" => "",
        "unique" => false,
        "note" => ""

    ));

    $attribute = Mage::getSingleton("eav/config")->getAttribute("customer", $code);


    $used_in_forms = array();

    $used_in_forms[] = "adminhtml_customer";
    $used_in_forms[] = "checkout_register";
    $used_in_forms[] = "customer_account_create";
    $used_in_forms[] = "customer_account_edit";
    $used_in_forms[] = "adminhtml_checkout";
    $attribute->setData("used_in_forms", $used_in_forms)
        ->setData("is_used_for_customer_segment", true)
        ->setData("is_system", 0)
        ->setData("is_user_defined", 1)
        ->setData("is_visible", 0)
        ->setData("sort_order", 100);
    $attribute->save();

}

$installer->endSetup();

