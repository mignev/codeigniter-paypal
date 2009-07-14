<?php
/**
  * Paypal IPN Library
  *
  * Requires the PHP cURL extension
	* Had some help from Elliot "Haughinator" Haugin on this one, so props to him
  *
  * @package Flame
  * @subpackage Paypal
  * @copyright 2009, Jamie Rumbelow
  * @author Jamie Rumbelow <http://www.jamierumbelow.net>
  * @license GPLv3
  * @version 1.0.1
  */
  
class Ipn {
	
	private $ipn_data;
	private $ipn_url;
	
	private $curl;
	
	public function __construct() {
		$this->add_data('cmd', '_xclick');
		$this->add_data('rm', 2);
		$this->curl = curl_init();
		
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_HEADER , 0);
		curl_setopt($this->curl, CURLOPT_VERBOSE, 1);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, 120);
	}
	
	public function set_url($url) {
		$this->ipn_url = $url;
	}
	
	public function add_data($key, $value) {
		if ($key == 'custom') {
			$value = json_encode($value);
		}
		$this->ipn_data[$key] = $value;
	}
	
	public function request() {
		//Build up the HTML page
		$string = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
		$string .= '<html>';
			$string .= '<head>';
				$string .= '<title>Redirecting to PayPal...</title>';
				$string .= '<script type="text/javascript">
	window.onload = function() {
		document.forms[\'paypal_form\'].submit();
	}
</script>';
			$string .= '</head>';
			$string .= '<body>';
				$string .= '<h2>Redirecting to PayPal</h2>';
				$string .= '<form action=\''.$this->ipn_url.'\' method=\'post\' name=\'paypal_form\'>';
					foreach ($this->ipn_data as $key => $value) {
						$string .= '<input type=\'hidden\' name=\''.$key.'\' value=\''.$value.'\' />';
					}
				$string .= '<p>If you don\'t get redirected to PayPal within five seconds <input type=\'submit\' value=\'click here!\' /></p>';
				$string .= '</form>';
			$string .= '</body>';
		$string .= '</html>';
		
		echo($string);
		exit;
	}
	
	public function validate() {
		$post_string = "cmd=_notify-validate";
		
		foreach ( $_POST as $key => $value )
		{
			$this->add_data($key, $value);
			
			$value = urlencode(stripslashes($value));
			$post_string .= "&$key=$value";
		}
		
		$new_custom = json_decode(base64_decode(base64_decode($this->ipn_data['custom'])));
		$this->decrypted_custom = array();

		foreach ( $new_custom as $key => $value )
		{
			$this->decrypted_custom[$key] = $value;
		}
		
		if ( $this->ipn_data['payment_status'] !== 'Completed' )
		{
			return FALSE;
		}
		
		curl_setopt($this->curl, CURLOPT_URL, $this->ipn_url);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post_string);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded", "Content-Length: " . strlen($post_string)));

		$result = curl_exec($this->curl);
		
		if ( !$result )
		{
			return FALSE;
		}
		
		$valid_results = array('VERIFIED', 'INVALID');
		
		if ( !in_array($result, $valid_results) || !$result === 'INVALID' )
		{	
			return FALSE;
		}
		
		return array('paypal_data' => $this->ipn_data, 'custom_data' => $this->decrypted_custom);
	}
	
}