<?php

$dependencies = array(
    Mage::getBaseDir('lib') . DS . 'SweetTooth' . DS . 'SweetTooth.php',
    Mage::getBaseDir('lib') . DS . 'SweetTooth' . DS . 'etc' . DS . 'SdkException.php',
    Mage::getBaseDir('lib') . DS . 'SweetTooth' . DS . 'etc' . DS . 'ApiException.php'
);

foreach ($dependencies as $dependency) {
    if (file_exists($dependency) && is_readable($dependency)) {
        include_once($dependency);
    } else {
        $message = Mage::helper('rewards')->__("Wasn't able to load lib/SweetTooth.php.  Download rewardsplatformsdk.git and run the installer to symlink it.");
        Mage::getSingleton('core/session')->addError($message);
        Mage::helper('rewards/debug')->log($message);
        return $this;
    }
}

class TBT_RewardsLoyalty_Helper_Loyalty extends TBT_Common_Helper_LoyaltyAbstract
{
    protected $_oldTransfer = null;
    /**
     * Number of seconds before the authentication admin flag expires
     * @var int
     */
    protected $_refreshAuthTimer = 1800;  // 30 minutes

    const LOYALTY_INTERVAL_DEFAULT = 3600;  // 1 hour

    const KEY_LOYALTY_LOCKED = '/loyalty/locked';
    const KEY_LOYALTY_RULES = '/loyalty/rules';


    public function getModuleKey()
    {
        return 'rewards';
    }

    public function isValid()
    {
        return true;
    }

    /**
     * $param TBT_Common_Admin_AbstractController $action
     * @see TBT_Common_Helper_LoyaltyAbstract::onAdminPreDispatch()
     */
    public function onAdminPreDispatch($action)
    {
        parent::onAdminPreDispatch($action);

        $request = $action->getRequest();
        $data = $request->getPost();

        $ruleMgmtControllers = array(
            'manage_promo_quote',
            'manage_special'
        );

        $isRuleMgmtPage = in_array($request->getRequestedControllerName(), $ruleMgmtControllers);
        $isBehaviourRule = $request->getRequestedControllerName() == 'manage_special';
        $isSavingRule = $request->getRequestedActionName() == 'save';
        $isEnablingRule = array_key_exists('is_active', $data) ? $data['is_active'] : false;

        try {
            // stop merchant from enabling spending rules if their account is locked
            if ($isRuleMgmtPage && $isSavingRule && $isEnablingRule) {
                $isSpendingRule = !$isBehaviourRule && !$this->_isEarningRuleBasedOnData($data);
                if ($isSpendingRule) {
                    $platform = Mage::getSingleton('rewardsloyalty/platform_instance');
                    $loyalty = $platform->loyalty()->get();

                    Mage::getSingleton('admin/session')->setLastSweetToothApiAuthTime(time());

                    if ($loyalty['is_locked']) {
                        Mage::getSingleton('adminhtml/session')->addError("You can't enable this rule because you've exceeded your MageRewards transaction quota.  Please <a href='http://support.magerewards.com/' title='MageRewards Support' target='_blank'>contact support</a> to reactivate your account.");
                        $action->redirect($request->getRequestedRouteName() . '/' . $request->getRequestedControllerName(), array('type' => TBT_Rewards_Helper_Rule_Type::REDEMPTION));
                        $action->setFlag('', TBT_Common_Admin_AbstractController::FLAG_NO_DISPATCH, true);
                    }

                    return $this;
                }
            }

            // Check if the auth was already done within an acceptable timeframe this session. If so and it was valid, skip.
            $stpAuth = Mage::getSingleton('admin/session')->getLastSweetToothApiAuthTime();
            if(!empty($stpAuth) && ((time() - $stpAuth) < $this->_refreshAuthTimer)) {
                return $this;
            }
            $platform = Mage::getSingleton('rewardsloyalty/platform_instance');
            $platform->authenticate();
        } catch(SweetToothSdkException $ex) {
            $action->forwardToBillboard('rewardsloyalty/billboard_noAccount');

            return $this;
        } catch(SweetToothApiException $ex) {
            if ($ex->getCode() == SweetToothApiException::FORBIDDEN ||
                    $ex->getCode() == SweetToothApiException::UNAUTHORIZED
            ) {
                // TODO: this should point to a different billboard
                $action->forwardToBillboard('rewardsloyalty/billboard_noAccount');

                return $this;
            } else if ($ex->getCode() == SweetToothApiException::NOT_FOUND) {
                // if 404, most probably our servers can't be reached
                // so, if account is connected don't affect admin experience
                if (Mage::getStoreConfig('rewards/platform/is_connected')) {
                    return $this;
                }
            }

            // if it's any other type of error, chances are it's a Platform issue, so we'll just ignore it
        }

        // Remember that the auth was successful
        Mage::getSingleton('admin/session')->setLastSweetToothApiAuthTime(time());

        return $this;
    }

    public function onBlockBeforeToHtml($block)
    {
        parent::onBlockBeforeToHtml($block);

        // check if block is the edit page for an earning rule
        if ($block instanceof TBT_Rewards_Block_Manage_Special_Edit_Tab_Main ||
                (($block instanceof TBT_Rewards_Block_Manage_Promo_Quote_Edit_Tab_Main) &&
                $block->getRequest()->has('type') && $block->getRequest()->get('type') == TBT_Rewards_Helper_Rule_Type::DISTRIBUTION)) {

            try {
                $platform = Mage::getSingleton('rewardsloyalty/platform_instance');
                $loyalty = $platform->loyalty()->get();
            } catch (Exception $ex) {
                return $this;
            }

            if ($loyalty['is_locked']) {
                // disable the Status dropdown in the UI to discourage attempting to enable the rule
                $block->getForm()->getElement('is_active')->setDisabled(true);
            }
        }

        return $this;
    }

    public function onModelBeforeSave($model)
    {
        parent::onModelBeforeSave($model);

        if (Mage::getStoreConfig('rewards/platform/dev_mode') && !$model->getId()) {
            $model->setComments("[DEVELOPER MODE] " . $model->getComments())
                ->setIsDevMode(true);
        }

        return $this;
    }

    public function onModelAfterCommitCallback($model)
    {
        parent::onModelAfterCommitCallback($model);

        if ($model instanceof TBT_Rewards_Model_Transfer) {
            $this->_sendTransferToPlatform($model);
        }

        return $this;
    }

    /**
     * Formats a transfer's data so it is supported by the Platform
     * transfer.create API, then sends the transfer up.
     * @param TBT_Rewards_Model_Transfer $transfer
     */
    protected function _sendTransferToPlatform($transfer)
    {
        // Note: issued_by don't seem to be set anywhere within ST
        // we are not sending to Platform points imported by Admin (ST-2228)
        if ($this->_alreadySentToPlatform($transfer) || $transfer->getIsPointsImport()) {
            return $this;
        }

        $this->_oldTransfer = $transfer;

        $fields = array(
            'channel_transfer_id' => $transfer->getRewardsTransferId(),
            'channel_user_id' => $transfer->getCustomerId(),
            'quantity' => $transfer->getQuantity(),
            'comments' => $transfer->getComments(),
            'effective_start' => $transfer->getEffectiveStart(),
            'status' => $transfer->getStatusId(),
            'currency_id' => Mage::helper('rewards/currency')->getDefaultCurrencyId(),
            'reason_id' => $transfer->getReasonId(),
            'issued_by' => $transfer->getIssuedBy(),
            'last_update_by' => $transfer->getUpdatedBy(),
            'reference' => $this->_buildReferencesArray($transfer),
        );

        $customer = Mage::getModel('customer/customer')->load($transfer->getCustomerId());

        $fields['user'] = array(
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'email' => $customer->getEmail(),
        );

        $client = Mage::getSingleton('rewards/platform_instance');

        try {
            $client->transfer()->create($fields);
        } catch (Exception $e) {
            if ($e->getMessage()) {
                Mage::helper('rewards')->log($e->getMessage());
            } else {
                Mage::helper('rewards')->log("Empty Exception message on transfer.create; this is an intentional timeout for performance reasons and has no effect on the rewards program.  Transfer ID: [{$fields['channel_transfer_id']}]");
            }
        }

        return $this;
    }

    /**
     * This will check if this transfer was already sent to Platform. 
     *
     * @param  TBT_Rewards_Model_Transfer $transfer
     * @return bool
     */
    protected function _alreadySentToPlatform($transfer)
    {
        if (!$this->_oldTransfer) {
            return false;
        }

        if ($this->_oldTransfer->getId() != $transfer->getId()) {
            return false;
        }

        if ($this->_oldTransfer->getStatusId() != $transfer->getStatusId()) {
            return false;
        }

        return true;
    }

    /**
     * Loads all references of a transfer and converts them into an array
     * following a format supported by the transfer.create Platform API
     * @param TBT_Rewards_Model_Transfer $transfer
     */
    protected function _buildReferencesArray($transfer)
    {
        return array('reference_id' => $transfer->getReferenceId());
    }

    /**
     * This method should run once every hour (dependent upon such events as admin activity)
     */
    protected function recurringActionsHook()
    {
        parent::recurringActionsHook();

        try {
            $isLocked = Mage::getSingleton('rewardsloyalty/platform_instance')->loyalty()->get();
            $isLocked = $isLocked['is_locked'];
        } catch (SweetToothSdkException $ex) {
            // Missing API keys / subdomain / etc?  Treat it like their account is locked.
            Mage::helper('rewards')->log("Attempted to check quota usage but hit an SDK exception (missing API creds / missing subdomain / etc... probably harmless).  Recurring actions hook.");
            $isLocked = true;
        } catch (SweetToothApiException $ex) {
            if ($ex->getCode() == SweetToothApiException::FORBIDDEN ||
                $ex->getCode() == SweetToothApiException::UNAUTHORIZED
            ) {
                // Invalid API keys / etc?  Treat it like their account is locked.
                Mage::getSingleton('core/session')->addWarning(
                    Mage::helper('rewards')->__("Your MageRewards API credentials seem to be invalid."));
                $isLocked = true;
            } else if ($ex->getCode() == SweetToothApiException::NOT_FOUND) {
                if (Mage::getStoreConfig('rewards/platform/is_connected')) {
                    // if Platform can't be reach but account is connected, don't affect admin
                    return $this;
                }
            } else {
                // Can't find the Platform server?  Exit without re-enabling their rules; they're likely still locked.
                return $this;
            }
        } catch (Exception $ex) {
            // Can't find the Platform server?  Exit without re-enabling their rules; they're likely still locked.
            return $this;
        }

        // check local config to see if we THINK we're locked or not
        $isLockedLocally = $this->getConfigData(self::KEY_LOYALTY_LOCKED);

        if ($isLocked) {
            // get a list of all active earning rules
            $salesRules = $this->_fetchActiveSpendingRules();

            if (count($salesRules) > 0) {
                // save list of currently active earning rules into config so we can re-enable them later
                $disabledRuleIds = Zend_Json::encode(array(
                    'sales' => $salesRules->getAllIds()
                ));
                $this->setConfigData(self::KEY_LOYALTY_RULES, $disabledRuleIds);

                // disable active earning rules
                $this->_disableRules($salesRules);

                // set account as LOCKED locally
                $this->setConfigData(self::KEY_LOYALTY_LOCKED, true);
            }
        } else if (!$isLocked && $isLockedLocally) {
            try {
                $disabledRuleIds = Zend_Json::decode($this->getConfigData(self::KEY_LOYALTY_RULES));
                $this->_enableRules($disabledRuleIds);
                $this->setConfigData(self::KEY_LOYALTY_RULES, '[]');
                $this->setConfigData(self::KEY_LOYALTY_LOCKED, false);
            } catch (Exception $ex) {
                // TODO: should we log this? (probably just malformed JSON, which likely means hax0rs)
            }
        }

        return $this;
    }

    /**
     * Making sure that if the account is locked locally we ping the server without any delays.
     *
     * @return bool
     */
    protected function _shouldSkipInterval()
    {
        // check local config to see if we THINK we're locked or not
        $isLockedLocally = $this->getConfigData(self::KEY_LOYALTY_LOCKED);
        return $isLockedLocally;
    }

    /**
     * Fetches all active spending rules (sales) and returns them in an array
     * in the same order.
     */
    protected function _fetchActiveSpendingRules()
    {
        $salesRules = Mage::getResourceModel('salesrule/rule_collection')
            ->addFieldToFilter('points_action', array('notnull' => true))
            ->addFieldToFilter('points_discount_action', array('notnull' => true))
            ->addFieldToFilter('is_active', 1)
            ->load();

        return $salesRules;
    }

    /**
     * Takes an array of each type of rule, disables each rule, and saves them.
     * @return TBT_RewardsLoyalty_Helper_Loyalty
     */
    protected function _disableRules($salesRules)
    {
        foreach ($salesRules as $rule) {
            $rule->load($rule->getId())
                ->setIsActive(0)
                ->save();
        }

        return $this;
    }

    /**
     * Takes an array of all types of rules (grouped by each respective key: 'sales'),
     * enables each rule, and saves them.
     * @return TBT_RewardsLoyalty_Helper_Loyalty
     */
    protected function _enableRules($groupedRuleIds)
    {
        if (is_array($groupedRuleIds['sales'])) {
            foreach ($groupedRuleIds['sales'] as $ruleId) {
                $rule = Mage::getModel('salesrule/rule')->load($ruleId);
                if ($rule->getId()) {
                    $rule->setIsActive(true)->save();
                }
            }
        }

        return $this;
    }

    protected function _isEarningRuleBasedOnData($data)
    {
        // if points action begins with 'give_', it is an earning rule
        return substr($data['points_action'], 0, 5) == 'give_';
    }
}
