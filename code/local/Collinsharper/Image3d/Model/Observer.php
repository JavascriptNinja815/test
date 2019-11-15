<?php


class Collinsharper_Image3d_Model_Observer
{
 CONST REELNAMEOPTIONID  = 11;
 CONST VIEWER_REELNAMEOPTIONID  = 9;
 CONST IMPRINT_REELNAMEOPTIONID  = 43;

    public function _validUserReel($reelId)
    {
        mage::log(__METHOD__ . __LINE__ . " no more legacy reels");
        $customerId = Mage::getSingleton('customer/session')->getCustomerId();
        if($customerId) {
            $model = Mage::getModel('chreels/reels')->load($reelId);
            // TODO support admin access to all?
            return $model && $model->getId() == $reelId && $customerId == $model->getCustomerId();
        }
        return false;
    }


    /**
     * This is extended to test for more items that logged in.
     * @param $observer
     * @return Collinsharper_Image3d_Model_Observer
     */
    public function frontControllerActionCheckoutLoggedIn($observer)
    {

        $controller = Mage::app()->getRequest()->getControllerName();
        $action = Mage::app()->getRequest()->getActionName();

        $reelId = false;
        $reelOpt = 9;
        $forceOut = false;



        // verify the user has access to the reel on add to cart and checkout index.

        if($controller == 'product' && isset($_GET['id'])) {
            mage::log(__METHOD__ . __LINE__ . " we Prouct and Reel " . $_GET['id']);
            $reelId = (int)$_GET['id'];

        } else if($controller == 'cart' && isset($_POST['options']) && isset($_POST['options'][$reelOpt])) {
                $reelId = $_POST['options'][$reelOpt];
        } else if ($controller == 'checkout' && $action == 'index') {
            // loop over all  cart and reel id or Reel ids..
            $session = Mage::getSingleton('checkout/session');
            foreach ($session->getQuote()->getAllItems() as $item) {
                $helper = Mage::helper('catalog/product_configuration');
                $_options = $helper->getCustomOptions($item);
                if(!empty($_options)) {
                    foreach($_options as $_option) {
                        if($_option['label'] == 'Reel Name') {
                            if(!$this->_validUserReel($_option['id_value'])) {
                                $forceOut = true;
                                mage::log(__METHOD__ . __LINE__ . " forcing logout due to non ownership of reel " );

                            }
                        }
                    }
                }
            }
            $reelId = false;
        }

        if($reelId) {
            $forceOut = $forceOut || !$this->_validUserReel($reelId);
            mage::log(__METHOD__ . __LINE__ . " forcing logout due to non ownership of reel " );

        }

        if($forceOut) {
            Mage::getSingleton('customer/session')->logout();
            header('Location: ' . Mage::helper('customer')->getLoginUrl());
            mage::log(__METHOD__ . __LINE__ . " forcing logout due to non ownership of reel " );

            exit;
        }



        $tests = array('/cart' => false, '/onepage' => false, 'stereo/orderform' => true,
        //'market/order/order-form' => true // this one we allow guests.
        );
        
        //$uri = (string)Mage::app()->getRequest()->getControllerName();
        $uri = (string)Mage::helper('core/url')->getCurrentUrl();
        $uri = parse_url($uri,PHP_URL_PATH);
    //    mage::log(__METHOD__ . " uri " .  $uri);

        $isSkip = false;
        $redirect = false;
        foreach($tests as $test => $setAfterLogin) {
            $isSkip = strstr($uri, $test);
            if($isSkip !== false) {
                $redirect = $setAfterLogin;
                break;
            }
        }

        if(!$redirect && 'market' == mage::app()->getStore()->getCode()) {
            mage::log(__METHOD__ . " SKIP store  " . mage::app()->getStore()->getCode());
            return $this;
        }


        if($isSkip !== false && !Mage::helper('customer')->isLoggedIn()) {
            // Mage::app()->getResponse()->setRedirect('customer');
            //$response = $observer->getResponse();
            $response = Mage::app()->getResponse();
            if($redirect) {
                $redirectUrl = $this->getRequest()->getRequestUri();
                $redirectUrl = Mage::helper('core/url')->getCurrentUrl();
                mage::log(__METHOD__ . " REDIR " .  $redirectUrl);
                Mage::getSingleton('customer/session')->setBeforeAuthUrl($redirectUrl);
            }

            //$response->setRedirect(Mage::helper('customer')->getLoginUrl());
	header('Location: ' . Mage::helper('customer')->getLoginUrl());

	mage::log(__METHOD__ . __LINE__ . " we redir/" . Mage::helper('customer')->getLoginUrl());
exit;
        }
    }

    public function getRequest()
    {
        return Mage::app()->getRequest();
    }

    /**
     * Event listener for redirection event
     *
     * Checks that current action is checkout/cart/add, reel id is set in POST param and appends it to the url
     *
     * @param $observer
     */
    public function controllerResponseRedirect($observer)
    {
        $transportObject = $observer->getTransport();
        $url = $transportObject->getUrl();
        $request = Mage::app()->getRequest();

        if (
            $request->getRouteName() !== 'checkout'
            || $request->getControllerName() !== 'cart'
            || $request->getActionName() !== 'add'
            || !$request->isPost()
        ) {
            return;
        }

        if (!Mage::getSingleton('checkout/session')->getMessages()->getErrors()){
            return;
        }

        if (!($options = $request->getParam('options')) || empty($options[self::IMPRINT_REELNAMEOPTIONID])) {
            return;
        }

        $reelId = $options[self::IMPRINT_REELNAMEOPTIONID];
        $transportObject->setUrl($url . '?id=' . $reelId);
    }


}
