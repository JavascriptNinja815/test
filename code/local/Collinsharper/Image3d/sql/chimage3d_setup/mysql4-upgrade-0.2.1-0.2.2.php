<?php

$installer = $this;

$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');

$installer->startSetup();

try {



    $content =<<< EOD
    <div>Enjoying your experience on Image3D? Try the new RetroViewer mobile app!<br />
Get it now on the
    <a style='color: #fb7a36; cursor:pointer;' onclick='return onclickGoToIosStore("https://itunes.apple.com/app/apple-store/id1455711666?mt=8")'>App Store!</a>
</div>

EOD;

				$staticBlock = array(
                    'title'		 => 'IOS customer dashboard marketing popup',
                    'identifier' => 'ios-dashboard-popup',
                    'content' 	 => $content,
                    'is_active'  => 1,
                    'stores' => array(0)
                );

				Mage::getModel('cms/block')->setData($staticBlock)->save();



} catch (Exception $e) {


    mage::logException($e);
}





$installer->endSetup();
