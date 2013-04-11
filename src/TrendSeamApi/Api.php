<?php

namespace TrendSeamApi;

class Api {
	
	private $_client_api_key;
	private $_protocol = 'http://';
	private $_sandbox_api_url = 'api.sandbox.trendseam.com/api/v1/';
	private $_live_api_url = 'api.trendseam.com/api/v1/';
	private $_sandbox_web_url = 'www.sandbox.trendseam.com/';
	private $_live_web_url = 'www.trendseam.com/';
	private $_current_api_url;

	public $data;
	
	const DEBUG_OFF = 0;
	const DEBUG_VERBOSE = 1;
	const DEBUG_RETURN = 2;
	const DEVELOPMENT_MODE_SANDBOX = 'sandbox';
	const DEVELOPMENT_MODE_LIVE = 'live';
	
	// Change these private vars to alter level of verbosity and environment 
	private $_debugMode = self::DEBUG_OFF;
	private $_developmentMode = self::DEVELOPMENT_MODE_SANDBOX;
	
	
	
	public function __construct() {
		
		$this->_default('TRENDSEAM_API_KEY', null);
		
		if($this->_developmentMode == 'live') {
			$this->_current_api_url = $this->_protocol.$this->_live_api_url;
			$this->_current_web_url = $this->_protocol.$this->_live_web_url;
		} else {
			$this->_current_api_url = $this->_protocol.$this->_sandbox_api_url;
			$this->_current_web_url = $this->_protocol.$this->_sandbox_web_url;
		}
		
		
	}
	
	public function installURL($store, $callback_url, $vendor=false)
    {
        return $this->_current_web_url."integrate".($vendor?'/'.$vendor:'')."?store=".$store."&callback=".$callback_url;
    }
	
	public function format_order($order) {
		
		$o = new \stdClass;
		$o->OrderNumber = $order['name']; // This was ['id'], but name is the human readable, incremented value
		$o->OrderDate = $order['created_at'];
		$o->SubTotal = $order['subtotal_price'];
		$o->DeliveryCost = null; // No single value
		$o->DeliveryTax = null; // No single value
		$o->TaxTotal = $order['total_tax'];
		$o->GrandTotal = $order['total_price'];
		$o->Currency = $order['currency'];
		$o->Cashier = null; // No mappable value
		$o->SalesAssistant = null; // No mappable value (same?)
		$o->SalesChannel = 'Shopify'; // Is this ok?
		$o->BillingAddress = new \stdClass;
		$o->ShippingAddress = new \stdClass;
		$o->Customer = new \stdClass;
		$o->OrderItems = [];
		
		// Billing Address
		$o->BillingAddress->Line1 = $order['billing_address']['address1'];
		$o->BillingAddress->Line2 = $order['billing_address']['address2'];
		$o->BillingAddress->Line3 = null; // No mappable value
		$o->BillingAddress->City = $order['billing_address']['city'];
		$o->BillingAddress->County = $order['billing_address']['province'];
		$o->BillingAddress->Postcode = $order['billing_address']['zip'];
		$o->BillingAddress->CountryCode = $order['billing_address']['country_code'];
		$o->BillingAddress->Phone = $order['billing_address']['phone'];
		$o->BillingAddress->CompanyName = $order['billing_address']['company'];
		$o->BillingAddress->PhoneNumber = $order['billing_address']['phone']; // Repetition?
		
		// Shipping Address
		$o->ShippingAddress->Line1 = $order['shipping_address']['address1'];
		$o->ShippingAddress->Line2 = $order['shipping_address']['address2'];
		$o->ShippingAddress->Line3 = null; // No mappable value
		$o->ShippingAddress->City = $order['shipping_address']['city'];
		$o->ShippingAddress->County = $order['shipping_address']['province'];
		$o->ShippingAddress->Postcode = $order['shipping_address']['zip'];
		$o->ShippingAddress->CountryCode = $order['shipping_address']['country_code'];
		$o->ShippingAddress->Phone = $order['shipping_address']['phone'];
		$o->ShippingAddress->CompanyName = $order['shipping_address']['company'];
		$o->ShippingAddress->PhoneNumber = $order['shipping_address']['phone']; // Repetition?
		
		// Customer Details
		$o->Customer->RetailerReference = $order['customer']['id'];
		$o->Customer->Title = null; // From shipping or payment?
		$o->Customer->FirstName = $order['customer']['first_name'];
		$o->Customer->MiddleName = null; // No mappable value
		$o->Customer->LastName = $order['customer']['last_name'];
		$o->Customer->Address = new \stdClass;
		$o->Customer->Email = $order['customer']['email'];
		$o->Customer->MobilePhone = null; // No mappable value
		$o->Customer->HomePhone = null; // No mappable value
		$o->Customer->WorkPhone = null; // No mappable value
		$o->Customer->OtherPhone = null; // No mappable value 
		$o->Customer->DateCreated = $order['customer']['created_at'];
		$o->Customer->DateOfBirth = null; // No mappable value
		$o->Customer->Gender = null; // No mappable value 
		
		// Customer Address
		$o->Customer->Address->Line1 = null; // No mappable value
		$o->Customer->Address->Line2 = null; // No mappable value
		$o->Customer->Address->Line3 = null; // No mappable value
		$o->Customer->Address->City = null; // No mappable value
		$o->Customer->Address->County = null; // No mappable value
		$o->Customer->Address->Postcode = null; // No mappable value
		$o->Customer->Address->CountryCode = null; // No mappable value
		$o->Customer->Address->Phone = null; // No mappable value
		$o->Customer->Address->CompanyName = null; // No mappable value
		$o->Customer->Address->PhoneNumber = null; // Repetition?
		
		if(!empty($order['line_items']) && is_array($order['line_items'])) {
			
			foreach($order['line_items'] as $line_item) {
			
				$li = new \stdClass;
				$li->Sku = $line_item['id']; // Was ['sku'] but this is optional in Shopify
				$li->Barcode = null; // No mappable value
				$li->VariantName = $line_item['variant_title'];
				$li->Quantity = $line_item['quantity'];
				$li->CostEach = $line_item['price'];
				$li->ItemPrice = $line_item['price']; // Is the same as cost each?
				$li->ItemTax = null; // No mappable value
				$li->LineTotal = null; // No mappable value
				$li->CreatedOn = null; // No mappable value
				$li->VariantSku = $line_item['variant_id']; // Is this right?
				$li->VariantCreatedOn = null; // No mappable value 
				$li->ProductName = $line_item['name'];
				// Vendor? Weight?
			
				$o->OrderItems[] = $li;
			
				unset($li);
			
			}
			
		}
		
		return $o;
			
	}
	
	
	public function push_order_data() {
	
		// Params to include/use
		// limit - limit
		// page - page to show
		// created_at_min = '2008-01-01 03:00'
		// created_at_max = '2008-01-01 03:00'
		// Should we show only fulfilled/financially fullfilled orders? Cancelled orders? - more params for these
		// Updated orders too
		
		$data = $this->_prepareData($this->data);
		$result = $this->transmit('orders',$data,'POST');
		return json_decode($result);
		
	}
	
	
	/**
	* Sends Communicates the data. Prints debug output if debug mode is turned on
	* @return the Class
	*/
	public function transmit($action,$data,$method="GET")
	{
		if (is_null(TRENDSEAM_API_KEY)) {
			throw new Exception('TrendSeam API key is not set');
		}
		// Add other exceptions here!
		
		// Data must not have carriage returns or spaces (linearise)
		
		$this->_current_api_url = $this->_current_api_url.$action;
		
		$data = $this->_prepareData($data);
		
		$headers = array(
			'Accept: application/json, text/javascript, */*',
			'Connection: keep-alive',
			'Content-Type: application/json; charset=UTF-8',
			'Authorization: Basic '.base64_encode(TRENDSEAM_API_KEY.':'.TRENDSEAM_API_KEY),
			'Accept-Encoding: gzip,deflate,sdch',
			'Accept-Language: en-GB,en-US',
			'Accept-Charset: ISO-8859-1,utf-8'
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_current_api_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		
		$return = curl_exec($ch);
		
		if ($this->_debugMode == self::DEBUG_VERBOSE) {
			print '<pre>';
			echo "JSON: " . json_encode($data) . "\nHeaders: \n\t" . implode("\n\t", $headers) . "\nReturn:\n$return";
			print '</pre>';
		
		} else if ($this->_debugMode == self::DEBUG_RETURN) {
			return array(
				'json' => json_encode($data),
				'headers' => $headers,
				'return' => $return
			);
		}
		
		if (curl_error($ch) != '') {
			throw new \Exception(curl_error($ch));
		}
		
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if (!$this->_isTwoHundred($httpCode)) {
			$message = json_decode($return);
			$message = isset($message->Message) ? $message->Message : '(failed to get message)';
			throw new \Exception("Error: TrendSeam returned HTTP code $httpCode with message ".$message);
		}
		
		return $return;
	}
	
	
	
	/**
	* Prepares the data array
	*/
	private function _prepareData($data)
	{
		return $data;
	}
	
	/**
	* If a number is 200-299
	*/
	private function _isTwoHundred($value)
	{
		return intval($value / 100) == 2;
	}
	
	/**
	* Defines a constant, if it isn't defined
	*/
	private function _default($name, $default)
	{
		if (!defined($name)) {
			define($name, $default);
		}
	}
	
	/**
	* Turns debug output on
	* @param int $mode One of the debug constants
	* @return Mail_Postmark
	*/
	public function &debug($mode = self::DEBUG_VERBOSE)
	{
		$this->_debugMode = $mode;
		return $this;
	}
	

}





