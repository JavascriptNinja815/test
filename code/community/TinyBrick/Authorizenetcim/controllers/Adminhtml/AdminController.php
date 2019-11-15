<?php
class TinyBrick_Authorizenetcim_Adminhtml_AdminController extends Mage_Core_Controller_Front_Action
{

	public function deleteAction()
	{

		$params = $this->getRequest()->getParams();
		$paymentId = $params['paymentId'];
		$profileId = $params['profileId'];
		
		/**
		 * This delets the profile
		 */
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml');
		$xml->deleteCustomerPaymentProfileRequest(array(
				'customerProfileId' => $profileId,
				'customerPaymentProfileId' => $paymentId
				));
		
		Mage::helper('authorizenetcim')->response($xml);
		
		$profileUpload = Mage::getModel('authorizenetcim/authorizenetcim');
		$profileUpload->load($paymentId, 'token_payment_profile_id')->delete();
	
	}
	
	public function updateAction()
	{
		
		$params = $this->getRequest()->getParams();
		$paymentId = $params['paymentId'];
		$profileId = $params['profileId'];
		$expMonth = $params['expMonth'];
		$expYear = $params['expYear'];
		$ccType = $params['ccType'];
		$cc = $params['cc'];
		
		/**
		 * This delets the profile
		 */
		$xml = Mage::getModel('authorizenetcim/authorizenetcim_authnetxml');
		$xml->updateCustomerPaymentProfileRequest(array(
						        'customerProfileId' => $profileId,
						        'paymentProfile' => array(
						            'payment' => array(
						                'creditCard' => array(
						                	'cardNumber' => $cc,
						                    'expirationDate' => $expYear . '-' . $expMonth
						                )
						            ),
						            'customerPaymentProfileId' => $paymentId
						        )
						    ));
		
		Mage::helper('authorizenetcim')->response($xml);
		
		$profileUpload = Mage::getModel('authorizenetcim/authorizenetcim');
		$profileUpload->load($paymentId, 'token_payment_profile_id'); 
		$profileUpload->setCcExpMonth($expMonth);
		$profileUpload->setCcExpYear($expYear);
		if(isset($ccType)){
			$profileUpload->setCcType($ccType);
		}
		$profileUpload->save();
		
	}
	
}