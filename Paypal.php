<?php
/**
  * Paypal Library
  *
  * Requires the PHP cURL extension
  *
  * @package Flame
  * @subpackage Paypal
  * @copyright 2009, Jamie Rumbelow
  * @author Jamie Rumbelow <http://www.jamierumbelow.net>
  * @license GPLv3
  * @version 1.0.1
  */
  
class Paypal {

	public $api_login = "";
	public $api_password = "";
	public $api_signature = "";
	
	public $mode = "test";
	
	private $curl;

	public function __construct() {
		$this->curl = curl_init();
      
	    curl_setopt($this->curl, CURLOPT_USERAGENT, "PayPalAPI+CodeIgniter/1.0.0");
	    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);
	    curl_setopt($this->curl, CURLOPT_POST, TRUE);
	    curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, FALSE);
	}
	
	public function setup($login, $password, $signature) {
		$this->api_login = $login;
		$this->api_password = $password;
		$this->api_signature = $signature;
	}
	
	/*
		WRAPPER METHODS
	*/
	public function do_capture($request) {	
		return $this->call('DoCapture', $request); 
	}
	
	public function do_authorization($request) {	
		return $this->call('DoAuthorization', $request); 
	}
  
	public function do_reauthorization($request) {	
		return $this->call('DoReauthorization', $request); 
	}
  
	public function do_void($request) {	
		return $this->call('DoVoid', $request); 
	}
  
	public function do_direct_payment($request, $card, $address, $details, $payer = array(), $payername = array(), $details_items = array(), $ebay = array(), $ship = array()) {
	  	$params = array_merge(
	  		$request, $card, $address,
			$details, $payer, $payername,
			$details_items, $ebay, $ship
		);
	  	
	  	return $this->call('DoDirectPayment', $params); 
	}
  
	public function set_express_checkout($request, $details, $address = array(), $details_items = array(), $ebay = array(), $agreement = array()) {
	  	$params = array_merge(
	  		$request, $details, $address,
	  		$details_items, $ebay, $agreement
	  	);
  	
		return $this->call('SetExpressCheckout', $params); 
	}
  
	public function get_express_checkout($token) {
		return $this->call('GetExpressCheckoutDetails', array('TOKEN' => $token)); 
	}
  
	public function do_express_checkout($request, $details, $ebay = array(), $details_items = array(), $address = array()) {
	  	$params = array_merge(
	  		$request, $details, $ebay,
			$details_items, $address
		);
	  	
	  	return $this->call('DoExpressCheckoutPayment', $params); 
	}
  
	public function get_transaction_details($transaction_id) {
  		return $this->call('GetTransactionDetails', array('TRANSACTIONID' => $transaction_id)); 
	}
  
	public function mass_pay($request, $details) {
	  	$params = array_merge($request, $details);
	  	
	  	return $this->call('MassPay', $params); 
	}
  
	public function refund_transaction($request) {
		$params = (array) $request;
	  	
		return $this->call('RefundTransaction', $params); 
	}

	public function transaction_search($request, $player) {
		$params = array_merge($request, $player);
		
		return $this->call('TransactionSearch', $params);
	}
	
	public function create_recurring_payments_profile($request, $details, $schedule, $billing, $card,  $address, $activation = array(), $ship = array(), $payer_info = array(), $payer_name = array()) {
	  	$params = array_merge(
	  		$request,
			$details,
			$schedule,
			$billing,
			$card, 
			$address,
			$activation,
			$ship,
			$payer_info,
			$payer_name
		);
		
  		return $this->call('DoDirectPayment', $params);
	}
	
	public function get_recurring_payments_profile_details($request) {
		return $this->call('GetRecurringPaymentsProfileDetails', $request);
	}
	
	public function manage_recurring_payments_profile_status($request) {
		return $this->call('ManageRecurringPaymentsProfileStatus', $request);
	}
	
	public function bill_outstanding_amount($request) {
		return $this->call('BillOutstandingAmount', $request);
	}
	
	public function update_recurring_payments_profile($request, $period, $card, $payer_info, $address, $ship = array()) {
		$params = array_merge(
			$request, $period, $card,
			$payer_info, $address, $ship
		);
		
		return $this->call('UpdateRecurringPaymentsProfile', $params);
	}
	
	public function get_billing_agreement_customer_details($request) {
  		return $this->call('GetBillingAgreementCustomerDetails', $request); 
	}

	public function ba_update($request) {
  		return $this->call('BillAgreementUpdate', $request); 
	}

	public function do_reference_transaction($request, $details, $card, $info, $address, $ship = array(), $item_type = array(), $ebay = array()) {
	  	$params = array_merge(
		  	$request, $details, $card,
			$info, $address, $ship,
			$item_type, $ebay
		);
		
  		return $this->call('DoReferenceTransaction', $params);
	}
	
	public function do_non_referenced_credit($request, $card, $info, $address) {
	  	$params = array_merge(
	  		$request, $card, $info, $address
	  	);
	  	
	  	return $this->call('DoNonReferencedCredit', $params); 
	}

	public function manage_pending_transaction_status($request) {
  		return $this->call('ManagePendingTransactionStatus', $request); 
	}

	public function get_balance($request = array()) {
  		return $this->call('GetBalance', $request); 
	}

	public function address_verify($request) {
  		return $this->call('AddressVerify', $request); 
	}
	
	/*
		CALLING METHODS
	*/
	public function call($method, $params) {  
		$required_params = array(
			'USER' 			=> $this->api_login, 
			'PWD'			=> $this->api_password,
			'VERSION'		=> '56.0',
			'SIGNATURE'		=> $this->api_signature,
			'METHOD'		=> $method
		);
	  
	    $url = $this->_build_url();
	    $fields = array_merge($required_params, $params);
			
	    curl_setopt($this->curl, CURLOPT_URL, $url);
	    curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($fields));
    
    	$response = curl_exec($this->curl);
		$response = explode("&", $response);
	    $array = array();
	      
	    foreach ($response as $item) {
	    	$a = explode("=", $item);
	    	$array[$a[0]] = urldecode($a[1]);
	    }
	    
	    return $array;
	}
	
	private function _build_url() {    
	    $url = "https://api-3t.";
	    $url .= ($this->mode == "test") ? "sandbox." : "";
	    
	    $url .= "paypal.com/nvp/";
	    
    return $url;  
  }

}