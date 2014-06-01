<?php

namespace TrendSeamApi;

class Api {
	
	private $_protocol = 'http://';
	private $_sandbox_api_url = 'api.sandbox.trendseam.com/api/v1/';
	private $_live_api_url = 'api.trendseam.com/api/v1/';
	private $_sandbox_web_url = 'www.sandbox.trendseam.com/';
	private $_live_web_url = 'www.trendseam.com/';
	private $_current_api_url;
	
	private $_trendseam_api_key = null;

	public $data;
	
	const DEBUG_OFF = 0;
	const DEBUG_VERBOSE = 1;
	const DEBUG_RETURN = 2;
	
	// Change these private vars to alter level of verbosity and environment 
	private $_debugMode = self::DEBUG_OFF; // DEBUG_OFF or DEBUG_VERBOSE or DEBUG_RETURN
	private $_developmentMode = 'live'; // Set to 'live' or 'sandbox'
	
	
	private function nullIfNotSet($val=null) {
		
		return isset($val) ? $val : null;
		
	}
	
	
	public function __construct($api_key=null) {
		
		$this->_trendseam_api_key = !is_null($api_key) ? $api_key : null;
		
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
	
	public function format_product($product) {
		
		$product = empty($product) ? [] : $product;

		
		$p = new \stdClass;
		$p->Name = $product['title'];
		$p->Sku = $product['id'];
		$p->DateCreated = $product['created_at'];
		
		$size_key = null;
		
		if (isset($product['options'])) {
			
			foreach($product['options'] as $k => $o) {
				
				if (preg_match('/size/i',$o['name']) === 1) $size_key = $k+1;
				
			}
			
		}
		
		$p->Variants = [];
		
		if(!empty($product['variants']) && is_array($product['variants'])) {
			
			foreach($product['variants'] as $variant) {
			
				$vt = new \stdClass;
				$vt->VariantSku = $variant['id'];
				$vt->Name = $variant['title'];
				$vt->Description = null;
				$vt->Barcode = self::nullIfNotSet($variant['barcode']);
				$vt->UnitPrice = is_null($variant['price']) ? 0 : $variant['price'];
				$vt->UnitTax = null;
				$vt->UnitCost = null;
				$vt->DateCreated = $variant['created_at'];
				$vt->Properties = [];
				if (isset($product['options'][0]['name'])) 
					$vt->Properties[$product['options'][0]['name']] = self::nullIfNotSet($variant['option1']);
				if (isset($product['options'][1]['name'])) 
					$vt->Properties[$product['options'][1]['name']] = self::nullIfNotSet($variant['option2']);
				if (isset($product['options'][2]['name'])) 
					$vt->Properties[$product['options'][2]['name']] = self::nullIfNotSet($variant['option3']);
				
				$vt->Size = isset($variant['option'.$size_key]) ? $variant['option'.$size_key] : null;
				
				$p->Variants[] = $vt;
			
				unset($vt);
			
			}
			
			
		}
		
		
		
		// $p->Options = $product['options'];
		// $p->SizeKey = $size_key;
		
		// print'<pre>';
		// print_r($p);
		// print'</pre>'; exit;

		unset($size_key);
		
		return $p;
			
	}
	
	
	public function format_order($order) {
		
		$order = empty($order) ? [] : $order;
		
		// Calculate Delivery Total
		$shipping_lines_total = 0;
		if(!empty($order['shipping_lines'])) {
			foreach ($order['shipping_lines'] as $shipping_line) {
				$shipping_lines_total += $shipping_line['price'];
			}
		}
		
		$o = new \stdClass;
		$o->OrderNumber = self::nullIfNotSet($order['name']); // This was ['id'], but name is the human readable, incremented value
		$o->OrderDate = self::nullIfNotSet($order['created_at']);
		$o->SubTotal = self::nullIfNotSet($order['subtotal_price']);
		$o->DeliveryCost = $shipping_lines_total; // Calculated
		$o->DeliveryTax = null; // No single value
		$o->TaxTotal = self::nullIfNotSet($order['total_tax']);
		$o->GrandTotal = self::nullIfNotSet($order['total_price']);
		$o->Currency = self::nullIfNotSet($order['currency']);
		$o->Cashier = null; // No mappable value
		$o->SalesAssistant = null; // No mappable value (same?)
		$o->SalesChannel = 'Shopify'; // Is this ok?
		$o->BillingAddress = new \stdClass;
		$o->ShippingAddress = new \stdClass;
		$o->Customer = new \stdClass;
		$o->Customer->Address = new \stdClass;
		$o->OrderItems = [];
		
		// Billing Address
		if(isset($order['billing_address'])) {
		$o->BillingAddress->Line1 = self::nullIfNotSet($order['billing_address']['address1']);
		$o->BillingAddress->Line2 = self::nullIfNotSet($order['billing_address']['address2']);
		$o->BillingAddress->Line3 = null; // No mappable value
		$o->BillingAddress->City = self::nullIfNotSet($order['billing_address']['city']);
		$o->BillingAddress->County = self::nullIfNotSet($order['billing_address']['province']);
		$o->BillingAddress->Postcode = self::nullIfNotSet($order['billing_address']['zip']);
		$o->BillingAddress->CountryCode = self::nullIfNotSet($order['billing_address']['country_code']);
		$o->BillingAddress->Phone = self::nullIfNotSet($order['billing_address']['phone']);
		$o->BillingAddress->CompanyName = self::nullIfNotSet($order['billing_address']['company']);
		$o->BillingAddress->PhoneNumber = self::nullIfNotSet($order['billing_address']['phone']); // Repetition?
		}
		
		// Shipping Address
		if(isset($order['shipping_address'])) {
		$o->ShippingAddress->Line1 = self::nullIfNotSet($order['shipping_address']['address1']);
		$o->ShippingAddress->Line2 = self::nullIfNotSet($order['shipping_address']['address2']);
		$o->ShippingAddress->Line3 = null; // No mappable value
		$o->ShippingAddress->City = self::nullIfNotSet($order['shipping_address']['city']);
		$o->ShippingAddress->County = self::nullIfNotSet($order['shipping_address']['province']);
		$o->ShippingAddress->Postcode = self::nullIfNotSet($order['shipping_address']['zip']);
		$o->ShippingAddress->CountryCode = self::nullIfNotSet($order['shipping_address']['country_code']);
		$o->ShippingAddress->Phone = self::nullIfNotSet($order['shipping_address']['phone']);
		$o->ShippingAddress->CompanyName = self::nullIfNotSet($order['shipping_address']['company']);
		$o->ShippingAddress->PhoneNumber = self::nullIfNotSet($order['shipping_address']['phone']); // Repetition?
		}
		
		
		// Customer Details
		
		if(isset($order['customer'])) {
			
			$customer_first_name = self::nullIfNotSet($order['customer']['first_name']);
			$customer_last_name = self::nullIfNotSet($order['customer']['last_name']);
			$customer_email = self::nullIfNotSet($order['customer']['email']);
			$customer_id = self::nullIfNotSet($order['customer']['id']);
			$customer_created_at = self::nullIfNotSet($order['customer']['created_at']);
		
		}
		
		
		// Remedial
		
		if (empty($customer_first_name)) {
			if (!empty($order['billing_address']['first_name'])) $customer_first_name = $order['billing_address']['first_name'];
			else if (!empty($order['shipping_address']['first_name'])) $customer_first_name = $order['shipping_address']['first_name'];
			else $customer_first_name = null;
		}
		
		if (empty($customer_last_name)) {
			if (!empty($order['billing_address']['last_name'])) $customer_last_name = $order['billing_address']['last_name'];
			else if (!empty($order['shipping_address']['last_name'])) $customer_last_name = $order['shipping_address']['last_name'];
			else $customer_last_name = null;
		}
		
		if (empty($customer_email)) {
			if (!empty($order['billing_address']['email'])) $customer_email = $order['billing_address']['email'];
			else if (!empty($order['shipping_address']['email'])) $customer_email = $order['shipping_address']['email'];
			else $customer_email = null;
		}
		
		if (empty($customer_id)) {
			if (!empty($customer_email)) $customer_id = $customer_email; // If no ID, revert to email
			else $customer_id = null;
		}
		
		if (empty($customer_created_at)) {
			$customer_created_at = date('Y-m-d H:i:s'); // If no date, use Now
		}
				
		$o->Customer->RetailerReference = $customer_id;
		$o->Customer->Title = null; // From shipping or payment?
		$o->Customer->FirstName = $customer_first_name;
		$o->Customer->MiddleName = null; // No mappable value
		$o->Customer->LastName = $customer_last_name;
		$o->Customer->Address = new \stdClass;
		$o->Customer->Email = $customer_email;
		$o->Customer->MobilePhone = null; // No mappable value
		$o->Customer->HomePhone = null; // No mappable value
		$o->Customer->WorkPhone = null; // No mappable value
		$o->Customer->OtherPhone = null; // No mappable value 
		$o->Customer->DateCreated = $customer_created_at;
		$o->Customer->DateOfBirth = null; // No mappable value
		$o->Customer->Gender = null; // No mappable value
		
		// Customer Address
		if(isset($order['billing_address'])) {
		$o->Customer->Address->Line1 = self::nullIfNotSet($order['billing_address']['address1']);
		$o->Customer->Address->Line2 = self::nullIfNotSet($order['billing_address']['address2']);
		$o->Customer->Address->Line3 = null; // No mappable value
		$o->Customer->Address->City = self::nullIfNotSet($order['billing_address']['city']);
		$o->Customer->Address->County = self::nullIfNotSet($order['billing_address']['province']);
		$o->Customer->Address->Postcode = self::nullIfNotSet($order['billing_address']['zip']);
		$o->Customer->Address->CountryCode = self::nullIfNotSet($order['billing_address']['country_code']);
		$o->Customer->Address->Phone = self::nullIfNotSet($order['billing_address']['phone']);
		$o->Customer->Address->CompanyName = self::nullIfNotSet($order['billing_address']['company']);
		$o->Customer->Address->PhoneNumber = self::nullIfNotSet($order['billing_address']['phone']); // Repetition?
		}
		
		if(!empty($order['line_items']) && is_array($order['line_items'])) {
			
			foreach($order['line_items'] as $line_item) {
			
				// SKU
				$sku = self::nullIfNotSet($line_item['product_id']);
				if (empty($sku)) $sku = self::nullIfNotSet($line_item['sku']);
				if (empty($sku)) $sku = self::nullIfNotSet($line_item['title']);
				if (empty($sku)) $sku = self::nullIfNotSet($line_item['name']);
				// #TODO - what happens if we get to this stage and still no SKU? May never happen...
				
				// Variant SKU
				$variant_sku = self::nullIfNotSet($line_item['variant_id']);
				if (empty($variant_sku)) $variant_sku = self::nullIfNotSet($line_item['variant_title']);
				if (empty($variant_sku)) $variant_sku = self::nullIfNotSet($line_item['name']);
				
			
				$li = new \stdClass;
				$li->Sku = $sku; // Was ['sku'] but this is optional in Shopify
				$li->Barcode = null; // No mappable value
				$li->VariantName = self::nullIfNotSet($line_item['variant_title']);
				$li->Quantity = is_null($line_item['quantity']) ? 0 : $line_item['quantity'];
				$li->CostEach = null;
				$li->ItemPrice = is_null($line_item['price']) ? 0 : $line_item['price']; // Is the same as cost each?
				$li->ItemTax = null; // No mappable value
				$li->LineTotal = $li->Quantity * $li->ItemPrice; // We calculate this
				$li->CreatedOn = null; // No mappable value
				$li->VariantSku = $variant_sku; 
				$li->VariantCreatedOn = null; // No mappable value 
				$li->ProductName = self::nullIfNotSet($line_item['title']);
				// Vendor? Weight?
			
				$o->OrderItems[] = $li;
			
				unset($li);
			
			}
			
		}
		
		return $o;
			
	}
	
	
	public function push_product_data() {
	
		// Params to include/use
		// ??? See below.
		
		$data = $this->_prepareData($this->data);
		
		$result = $this->transmit('products',$data,'POST');
		return json_decode($result);
		
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
		if (is_null($this->_trendseam_api_key)) {
			throw new Exception('TrendSeam API key is not set');
		}
		// Add other exceptions here!
		
		// Data must not have carriage returns or spaces (linearise)
		
		$current_api_url = $this->_current_api_url.$action;
		
		$data = $this->_prepareData($data);
		
		$headers = array(
			'Accept: application/json, text/javascript, */*',
			'Connection: keep-alive',
			'Content-Type: application/json; charset=UTF-8',
			'Authorization: Basic '.base64_encode($this->_trendseam_api_key.':'.$this->_trendseam_api_key),
			'Accept-Encoding: gzip,deflate,sdch',
			'Accept-Language: en-GB,en-US',
			'Accept-Charset: ISO-8859-1,utf-8'
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $current_api_url);
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
			$a = array(
				'json' => json_encode($data),
				'headers' => $headers,
				'return' => $return
			);
			return $a;
		}
		
		if (curl_error($ch) != '') {
			throw new \Exception(curl_error($ch));
		}
		
		// This SHOULD work, but doesn't because the TrendSeam API doesn't throw accurate header codes,
		// Instead, it sends it's own messages, which we need to check for... see below...
		//
		//	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// if (!$this->_isTwoHundred($httpCode)) {
		// 			$message = json_decode($return);
		// 			$message = isset($message->Message) ? $message->Message : '(failed to get message)';
		// 			throw new \Exception("Error: TrendSeam returned HTTP code $httpCode with message ".$message);
		// 		}
		
		$api_errors = $this->_anyApiErrors($return);
		
		if ($api_errors !== false) {
			
			foreach ($api_errors as $api_error) {
				throw new \Exception("TrendSeam API Error: ".$api_error);
			}
			
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
	
	private function _anyApiErrors($api_response=null) {
		$obj = json_decode($api_response);
		$errors = [];
		
		// #TODO - check JSON for errors like 
		// {"responseStatus":{"errorCode":"Invalid UserName or Password","message":"Invalid UserName or Password"}}
		// return array of error strings
		
		if (is_object($obj) && isset($obj->responseStatus)) {
			
			if (isset($obj->responseStatus->errorCode)) $errors[] = '['.$obj->responseStatus->errorCode.'] '.(isset($obj->responseStatus->message) ? $obj->responseStatus->message : 'No message given');
			
		}
		
		return empty($errors) ? false : $errors;
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





