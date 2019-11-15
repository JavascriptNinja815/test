<?php
class Image3D_Reels_ReelController extends Mage_Core_Controller_Front_Action {
	public function duplicateAction() {
		$helper = Mage::helper('reels/reels');
		$db = $helper->image3DIncludes();
		
		$reel_id = Mage::app()->getRequest()->getParam('id');

		$dr = new Reel($reel_id);
		$dr->duplicate();

        Mage::getSingleton('customer/session')->addSuccess($this->__('Reel was successfully duplicated.'));
		$this->_redirect('customer/account');
	}

	public function deleteAction() {
		$helper = Mage::helper('reels/reels');
		$db = $helper->image3DIncludes();
		
		$reel_id = Mage::app()->getRequest()->getParam('id');

		$dr = new Reel($reel_id);
		$user = $db->db_select_assoc('user_reels','user_id',array('reel_id'=>$dr->id),array('%d'));
		
		if(count($user) < 1 || $user[0]['user_id'] != Mage::getSingleton('customer/session')->getCustomer()->getID()) {
			Mage::getSingleton('checkout/session')->addError('Could not delete reel.');
			$this->_redirect('customer/account');
			return false;
		}

		$dr->delete();
        Mage::getSingleton('customer/session')->addSuccess($this->__('Reel was successfully deleted.'));
		$this->_redirect('customer/account');
	}
}
?>