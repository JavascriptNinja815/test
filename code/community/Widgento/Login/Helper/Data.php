<?php

/**
 * Widgento_Login
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0), a
 * copy of which is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Widgento
 * @package    Widgento_Login
 * @author     Yury Ksenevich <info@widgento.com>
 * @copyright  Copyright (c) 2012-2013 Yury Ksenevich p.e.
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */


?><?php

class Widgento_Login_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getCustomerStoreId($customerId)
    {
        if (!$customerId)
        {
            return false;
        }
    
        $customer = Mage::getModel('customer/customer')->load($customerId);
    
        $customerStore = Mage::app()->getStore($customer->getStoreId());
 
        if ($customerStore && $customerStore->getIsActive())
        {
            return $customer->getStoreId();
        }
    
        if ($customerStore)
        {
            $customerWebsite = Mage::app()->getWebsite($customerStore->getWebsiteId());
    
            foreach ($customerWebsite->getStores() as $websiteStore)
            {
                if ($websiteStore->getIsActive())
                {
                    return $websiteStore->getId();
                }
            }
        }
    
        if (0 == Mage::getStoreConfig('customer/account_share/scope'))
        {
            return Mage::app()->getDefaultStoreView()->getId();
        }
    }
}
