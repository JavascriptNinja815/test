<?php


class Collinsharper_Beanstreamprofiles_PaymentController extends Mage_Core_Controller_Front_Action
{

	protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

	protected function _getCsession()
    {
        return Mage::getSingleton('customer/session');
    }

	public function indexAction()
    {
        $this->_redirect('/');
		return;
    }

 	protected function help()
	{
		return Mage::helper('beanstreamprofiles');
	}

	public function redirectAction()
    {
		//mage::log(__CLASS__ );
		// if ! $this->_getSession()->getBeanstreamOrderId();
		// quit?
		$orderId = $this->_getSession()->getBeanstreamOrderId();
		$order = Mage::getModel('sales/order')->load($orderId);
			//$order->setStatus('pending_beanstreamprofilevbv');
		   $order->setState(
		   Collinsharper_Beanstreamprofiles_Model_PaymentMethod::PENDINGVBV, Collinsharper_Beanstreamprofiles_Model_PaymentMethod::PENDINGVBV,
		   $this->help()->__('Pending VBV / 3DS callback'),
		   false
		);
		$order->save();
		// we will need this again on the return
		//$this->_getSession()->setBeanstreamOrderId(false);

		$redir = $this->_getSession()->getBeanstreamRedirect();
		$this->_getSession()->setBeanstreamRedirect(false);
		mage::log(__CLASS__ . __FUNCTION__ . "redirect bits " . print_r($this->_getSession()->getBeanstreamRedirect(),1));
		mage::log(__CLASS__ . __FUNCTION__ . "redirect bits " . print_r($redir,1));
        echo $redir;
            exit;
		$redir = str_replace('\"','"',urldecode($redir));
		$this->getResponse()->setBody($redir);
		return;
    }

    public function returnAction()
    {
		if($this->_getSession()->getBeanstreamDeposit())
		{
			$this->_getSession()->setBeanstreamDeposit(false);
			$PaRes = $this->getRequest()->getParam('PaRes');
			$md = $this->getRequest()->getParam('MD');
			if(!$md || !$PaRes)
			{
				// die?
				
			}
			$return = $this->help()->completeVbvTransaction(array('PaRes' => $PaRes,'MD' => $md));
			if($return['trnApproved'] != 1)
			{
				$this->_getCsession()->addError($return['messageText']);
				$this->_redirect('orderdeposit/index/depositmethods', array('_secure' => true));
				return;
			}
				$html = '<html><body><p style="background-color: #FFFFFF">';
				$html .= "\n\n\n\n</p>";
			//	$html .= mage::helper('core/js')->getJsUrl('prototype/prototype.js', array('_secure' => true));
					// parent.document.getElementById(\'netbanxmessage\').innerHTML = "<h1>Payment Complete!</h1><b> Please check your email.</b>"; 
					// parent.document.getElementById("centered_dialog").remove();
				$html .= '<script type="text/javascript">
						console.log("we have success");
						//window.opener.setDepositMade();
						parent.document.getElementById("onestepcheckout_popup_overlay").remove();
						parent.document.getElementById("my_overlay_modal").remove();
						parent.document.getElementById("onestepcheckout-form").submit();
						//window.opener.destroyTerms();
					</script>';
				$html.= '</body></html>';

				$this->getResponse()->setBody($html);
				return;
		}
			
		//mage::log(__CLASS__ . __LINE__ . print_r($_POST,1));
		// get 
		$PaRes = $this->getRequest()->getParam('PaRes');
		$md = $this->getRequest()->getParam('MD');
		$orderId = $this->_getSession()->getBeanstreamOrderId();
		if(!$orderId)
		{
			exit;
		}
		
		$order = Mage::getModel('sales/order')->load($orderId);

		if(!$md || !$PaRes)
		{
		   $order->setState(
		   $order->getState(), $order->getState(),
		   $this->help()->__('Failed VBV / 3DS callback parameters.'),
		   false
		);
			$order->cancel();
			$order->save();
			$this->_getSession()->addError($this->help()->__('Your Order Failed VBV / 3DS Processing.'));
		}
		$return = $this->help()->completeVbvTransaction(array('PaRes' => $PaRes,'MD' => $md));
		if($return['trnApproved'] != 1)
		{
			// payment failed.
			// they have to start over:/
			//unles we can cancel the order and reactivate the quote?
		   $order->setState(
		   'canceled', 'canceled',
		   $this->help()->__('Failed VBV / 3DS Capture.: %s',$return['messageText']),
		   false
		);
			$order->cancel();
			$order->save();
			$this->_getSession()->setQuoteId($this->_getSession()->getBeanstreamQuoteId(true));
			$this->_getSession()->getQuote()->setIsActive(true)->save();
			$this->_getSession()->addError($this->help()->__('Your Order Failed VBV / 3DS Processing. Please try again.'));
		
			$this->_redirect('checkout/cart');
			return;
		}

		if($return['trnApproved'] == 1)
		{
			$order_status = 'pending';
			if(Mage::getStoreConfig('payment/beanstreamprofiles/payment_action') == Mage_Paygate_Model_Authorizenet::ACTION_AUTHORIZE_CAPTURE)
			{
				$order_status = 'processing';
			}
			$order->setState(
				$order_status, $order_status,
				$this->help()->__('Order Passed VBV / 3DS Capture.: %s',$return['messageText']),
				true
			);
			$payment = $order->getPayment();
			$payment->setStatus('APPROVED');
            $payment->setLastTransId($return['trnId']);
            $payment->setCcTransId($return['trnId'])
				->setCcAvsStatus($return['avsMessage'])
				->setCcCidStatus($return['cvdId'])
				->setIsTransactionClosed(0);
			$payment->save();
			$order->save();
			try {
				$order->sendNewOrderEmail();
			} catch (Exception $e) {
				Mage::logException($e);
			}
			$this->_redirect('checkout/onepage/success/',array('_secure' =>true));
			return;
		}
		exit;
   }

}