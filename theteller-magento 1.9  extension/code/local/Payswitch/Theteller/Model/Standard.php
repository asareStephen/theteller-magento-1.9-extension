<?php
class Payswitch_Theteller_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	protected $_code = 'theteller';
	
	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;
	
	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('theteller/payment/redirect', array('_secure' => true));
	}
}
?>