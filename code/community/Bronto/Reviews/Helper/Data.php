<?php

/**
 * @package     Bronto\Reviews
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Reviews_Helper_Data
    extends Bronto_Common_Helper_Data
    implements Bronto_Common_Helper_DataInterface
{
    const XML_PATH_ENABLED        = 'bronto_reviews/settings/enabled';
    const XML_PATH_STATUS         = 'bronto_reviews/settings/status';
    const XML_PATH_CANCEL_STATUS  = 'bronto_reviews/settings/cancel_status';
    const XML_PATH_URL_SUFFIX     = 'bronto_reviews/settings/url_suffix';
    const XML_PATH_PERIOD         = 'bronto_reviews/settings/period';
    const XML_PATH_MESSAGE        = 'bronto_reviews/settings/message';
    const XML_PATH_SENDER_EMAIL   = 'bronto_reviews/settings/sender_email';
    const XML_PATH_SENDER_NAME    = 'bronto_reviews/settings/sender_name';
    const XML_PATH_REPLY_TO       = 'bronto_reviews/settings/reply_to';
    const XML_PATH_DEFAULT_COUPON = 'bronto_reviews/settings/default_coupon';
    const XML_PATH_DEFAULT_REC    = 'bronto_reviews/settings/default_recommendation';

    /**
     * Gets the canonical name for the Bronto Review module
     *
     * @return string
     */
    public function getName()
    {
        return 'Bronto Review Requests';
    }

    /**
     * Check if module is enabled
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return bool
     */
    public function isEnabled($scope = 'default', $scopeId = 0)
    {
        // Get Enabled Scope
        return (bool)$this->getAdminScopedConfig(self::XML_PATH_ENABLED, $scope, $scopeId);
    }

    /**
     * Disable Module for Specified Scope
     *
     * @param string $scope
     * @param int    $scopeId
     * @param bool   $deleteConfig
     *
     * @return bool
     */
    public function disableModule($scope = 'default', $scopeId = 0, $deleteConfig = false)
    {
        return $this->_disableModule(self::XML_PATH_ENABLED, $scope, $scopeId, $deleteConfig);
    }

    /**
     * Get Order Status at which to send Review Request Emails
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewSendStatus($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_STATUS, $scope, $scopeId);
    }

    /**
     * Gets the coupon code selected for this review
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getDefaultRule($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_DEFAULT_COUPON, $scope, $scopeId);
    }

    /**
     * Gets the product recommendation selected for this review
     *
     * @param string $scope
     * @param int $scopeId
     * @return string
     */
    public function getDefaultRecommendation($scope = 'default', $scopeId)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_DEFAULT_REC, $scope, $scopeId);
    }

    /**
     * Get Order Status at which to cancel Review Request Emails
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return array
     */
    public function getReviewCancelStatus($scope = 'default', $scopeId = 0)
    {
        $status = $this->getAdminScopedConfig(self::XML_PATH_CANCEL_STATUS, $scope, $scopeId);
        if ($status != '') {
            $status = explode(',', $status);
        } else {
            $status = array();
        }

        return $status;
    }

    /**
     * Get suffix to append to product URLs
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getProductUrlSuffix($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_URL_SUFFIX, $scope, $scopeId);
    }


    /**
     * Get Period to wait before sending Review Request
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewSendPeriod($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_PERIOD, $scope, $scopeId);
    }

    /**
     * Get Bronto Message to use for sending Review Request Email
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewSendMessage($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_MESSAGE, $scope, $scopeId);
    }

    /**
     * Get the review url for the product
     *
     * @param $product
     * @return string
     */
    public function getReviewsUrl($product, $storeId = null)
    {
        $url = Mage::getModel('core/url')->setStore($storeId);
        $params = array('id' => $product->getId());
        if ($product->getCategoryId()) {
            $params['category'] = $product->getCategoryId();
        } else {
            $categories = $product->getCategoryIds();
            $categoryId = end($categories);
            $params['category'] = $categoryId;
        }
        return $url->getUrl('review/product/list', $params);
    }

    /**
     * Get Sender Email Address
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewSenderEmail($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_SENDER_EMAIL, $scope, $scopeId);
    }

    /**
     * Get Sender Name
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewSenderName($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_SENDER_NAME, $scope, $scopeId);
    }

    /**
     * Get Reply-To Email Address
     *
     * @param string $scope
     * @param int    $scopeId
     *
     * @return mixed
     */
    public function getReviewReplyTo($scope = 'default', $scopeId = 0)
    {
        return $this->getAdminScopedConfig(self::XML_PATH_REPLY_TO, $scope, $scopeId);
    }
}
