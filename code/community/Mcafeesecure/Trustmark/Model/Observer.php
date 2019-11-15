<?php

class Mcafeesecure_Trustmark_Model_Observer
{
  public function sip($observer)
  {
    Mage::log("Tracking SIP");
    $orderIds = $observer->getData('order_ids');
    foreach ($orderIds as $orderId) {
      $order = Mage::getModel('sales/order')->load($orderId);
      $firstName = $order->getCustomerFirstname();
      $lastName = $order->getCustomerLastname();
      $host = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
      $email = $order->getCustomerEmail();
      $orderTotal = $order->getTotalDue();
      $countryCode = $order->getBillingAddress()->getCountryId();
      $endpointHost = "https://www.mcafeesecure.com";
      $sipReqUrl = $endpointHost . "/rpc/ajax?do=track-site-conversion&jsoncallback=f&t=purchase&s=7&o=" . urlencode($orderId) . "&e=" . urlencode($email) . "&fn=" . urlencode($firstName ). "&ln=" . urlencode($lastName) . "&c=" . urlencode($countryCode) . "&h=" . urlencode($host) . "&a=" . urlencode($orderTotal);

      Mage::log("SIP Request: " . $sipReqUrl);

      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $sipReqUrl);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      $response = curl_exec($curl);
      curl_close($curl);
    }
    return $this;
  }
}
