<?php
/****************************************************
 CallerService.php

 This file uses the constants.php to get parameters needed
 to make an API call and calls the server.if you want use your
 own credentials, you have to change the constants.php

 Called by TransactionDetails.php, ReviewOrder.php,
 DoDirectPaymentReceipt.php and DoExpressCheckoutPayment.php.

 ****************************************************/
global $API_UserName;
global $API_Password;
global $API_Signature;
global $API_Endpoint;
global $version;
global $AUTH_token;
global $AUTH_signature;


//require_once 'constants.php';

/* Live Details
 define('API_USERNAME', 'info_api1.gozype.com');
 define('API_PASSWORD', 'G8YYHPB7DAR8AQ9N');
 define('API_SIGNATURE', 'AFcWxV21C7fd0v3bYYYRCpSSRl31AaildJBsLRiKqc2eynfmcDr3bC-L');
 define('API_ENDPOINT', 'https://api-3t.paypal.com/nvp');
 define('PAYPAL_URL', 'https://www.paypal.com/webscr&cmd=_express-checkout&token=');
 */


/* Sandbox Details
 define('API_UserName', 'goluch_1329713899_biz_api1.hotmail.com');
 define('API_PASSWORD', '1329713931');
 define('API_SIGNATURE', 'AFcWxV21C7fd0v3bYYYRCpSSRl31A7yC8SHOMNveTSULADPC524mlIpJ');
 define('API_ENDPOINT', 'https://api-3t.sandbox.paypal.com/nvp');
 define('PAYPAL_URL', 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=');
 define('VERSION', '76.0');
 define('ACK_SUCCESS', 'SUCCESS');
 define('ACK_SUCCESS_WITH_WARNING', 'SUCCESSWITHWARNING');
 define('USE_PROXY',FALSE);
 define('PROXY_HOST', '127.0.0.1');
 define('PROXY_PORT', '808');
 */



if(defined('API_USERNAME'))
{
	$API_UserName=API_USERNAME;
}

if(defined('API_PASSWORD'))
$API_Password=API_PASSWORD;

if(defined('API_SIGNATURE'))
$API_Signature=API_SIGNATURE;

if(defined('API_ENDPOINT'))
$API_Endpoint =API_ENDPOINT;





$version=VERSION;
$version='76.0';

if(defined('SUBJECT'))
$subject = SUBJECT;
// below three are needed if used permissioning
if(defined('AUTH_TOKEN'))
$AUTH_token= AUTH_TOKEN;

if(defined('AUTH_SIGNATURE'))
$AUTH_signature=AUTH_SIGNATURE;

if(defined('AUTH_TIMESTAMP'))
$AUTH_timestamp=AUTH_TIMESTAMP;

class PaypalComponent extends Component{



	/**
	 * hash_call: Function to perform the API call to PayPal using API signature
	 * @methodName is name of API  method.
	 * @nvpStr is nvp string.
	 * returns an associtive array containing the response from the server.
	 */


	function hash_call($methodName,$nvpStr)
	{

		//declaring of global variables
		global $API_Endpoint,$version,$API_UserName,$API_Password,$API_Signature,$nvp_Header, $subject, $AUTH_token,$AUTH_signature,$AUTH_timestamp;
		// form header string
		$nvpheader=$this->nvpHeader();

		/*
		 echo $API_UserName;
		 echo $API_Password;
		 echo $API_Signature;
		 echo $API_Endpoint;
		 echo $version;
		 die('vari'); */

		//setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);

		//in case of permission APIs send headers as HTTPheders
		if(!empty($AUTH_token) && !empty($AUTH_signature) && !empty($AUTH_timestamp))
		{
			$headers_array[] = "X-PP-AUTHORIZATION: ".$nvpheader;
			 
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_array);
			curl_setopt($ch, CURLOPT_HEADER, false);
		}
		else
		{
			$nvpStr=$nvpheader.$nvpStr;
		}

		//if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
		//Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php
		if(USE_PROXY)
		curl_setopt ($ch, CURLOPT_PROXY, PROXY_HOST.":".PROXY_PORT);

		//check if version is included in $nvpStr else include the version.
		if(strlen(str_replace('VERSION=', '', strtoupper($nvpStr))) == strlen($nvpStr)) {
			$nvpStr = "&VERSION=" . urlencode($version) . $nvpStr;
		}

		$nvpreq="METHOD=".urlencode($methodName).$nvpStr;

		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch,CURLOPT_POSTFIELDS,$nvpreq);

		//getting response from server
		$response = curl_exec($ch);
		//print_r(curl_errno($ch));
		//print_r($response);
		//die('anil'); /* */

		//convrting NVPResponse to an Associative Array
		$nvpResArray=$this->deformatNVP($response);
		$nvpReqArray=$this->deformatNVP($nvpreq);
		$_SESSION['nvpReqArray']=$nvpReqArray;

		if (curl_errno($ch)) {
			// moving to display page to display curl errors
			$_SESSION['curl_error_no']=curl_errno($ch) ;
			$_SESSION['curl_error_msg']=curl_error($ch);
			$location = "APIError.php";
			//header("Location: $location");
		} else {
			//closing the curl
			curl_close($ch);
		}

		return $nvpResArray;
	}


	function nvpHeader()
	{

		global $API_Endpoint,$version,$API_UserName,$API_Password,$API_Signature,$nvp_Header, $subject, $AUTH_token,$AUTH_signature,$AUTH_timestamp;
		$nvpHeaderStr = "";


		if(defined('AUTH_MODE')) {
			//$AuthMode = "3TOKEN"; //Merchant's API 3-TOKEN Credential is required to make API Call.
			//$AuthMode = "FIRSTPARTY"; //Only merchant Email is required to make EC Calls.
			//$AuthMode = "THIRDPARTY";Partner's API Credential and Merchant Email as Subject are required.
			$AuthMode = "AUTH_MODE";
		}
		else {
				
			if((!empty($API_UserName)) && (!empty($API_Password)) && (!empty($API_Signature)) && (!empty($subject))) {
				$AuthMode = "THIRDPARTY";
			}
				
			else if((!empty($API_UserName)) && (!empty($API_Password)) && (!empty($API_Signature))) {
				$AuthMode = "3TOKEN";
			}
				
			elseif (!empty($AUTH_token) && !empty($AUTH_signature) && !empty($AUTH_timestamp)) {
				$AuthMode = "PERMISSION";
			}
			elseif(!empty($subject)) {
				$AuthMode = "FIRSTPARTY";
			}
		}
		switch($AuthMode) {
				
			case "3TOKEN" :
				$nvpHeaderStr = "&PWD=".urlencode($API_Password)."&USER=".urlencode($API_UserName)."&SIGNATURE=".urlencode($API_Signature);
				break;
			case "FIRSTPARTY" :
				$nvpHeaderStr = "&SUBJECT=".urlencode($subject);
				break;
			case "THIRDPARTY" :
				$nvpHeaderStr = "&PWD=".urlencode($API_Password)."&USER=".urlencode($API_UserName)."&SIGNATURE=".urlencode($API_Signature)."&SUBJECT=".urlencode($subject);
				break;
			case "PERMISSION" :
				$nvpHeaderStr = formAutorization($AUTH_token,$AUTH_signature,$AUTH_timestamp);
				break;
		}
		return $nvpHeaderStr;
	}





	/** This function will take NVPString and convert it to an Associative Array and it will decode the response.
	 * It is usefull to search for a particular key and displaying arrays.
	 * @nvpstr is NVPString.
	 * @nvpArray is Associative Array.
	 */

	function deformatNVP($nvpstr)
	{

		$intial=0;
		$nvpArray = array();


		while(strlen($nvpstr)){
			//postion of Key
			$keypos= strpos($nvpstr,'=');
			//position of value
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr,$intial,$keypos);
			$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] =urldecode( $valval);
			$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
		}
		return $nvpArray;
	}
	function formAutorization($auth_token,$auth_signature,$auth_timestamp)
	{
		$authString="token=".$auth_token.",signature=".$auth_signature.",timestamp=".$auth_timestamp ;
		return $authString;
	}

}
?>