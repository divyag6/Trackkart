<?php namespace App\Domain;

//use App\Domain\VendorURL;
use Hash;
use Crypt;
use Auth;
use Bus;
use App\Commands\GetAllOrders;
use Illuminate\Contracts\Support\MessageBag;
use Log;
use Exception;

class VendorResource
{
	var $vendor_auth_username;
	var $vendor_auth_password;
	var $cookie_filename;
	var $baseurl;
	var $loginurl;
	var $orderurl;
	var $agent;
	var $token;
	var $orders=array(array());
	var $vendor;
	var $link;
	var $callback;
	var $postfields=array();
	var $message;
	var $partOrderUrl;
	var $pos;
	var $result=false;
	var $phantomDir;
	var $casperFile;
	var $casperDir;
	var $configId;
	
	function __construct($config)
	{	
		$this->configId=$config['id'];
		$xml=simplexml_load_file($_SERVER['DOCUMENT_ROOT'].'/VendorURL.xml')or die("Error: Cannot create object");
		foreach ($xml->vendor as $value)
		{
			if ($config['vendor_id']==$value->attributes())
			{
				$this->vendor=(string)$value->vendorname;
				$this->baseurl=(string)$value->baseurl;
				$this->loginurl=(string)$value->loginurl;
				$this->orderurl=(string)$value->orderurl;
				$this->callback='getCookies';
				$this->cookie_filename=$value->cookiefile.$config['vendor_id'].Auth::id().$config['email'].'.txt';
				$this->agent=(string)$value->agent;
				$this->link=(string)$value->orderlink;
				
				if($config['vendor_id']==4)
				{
					//$this->phantomDir='PHANTOMJS_EXECUTABLE='.dirname($_SERVER['DOCUMENT_ROOT']).'/node_modules/phantomjs-2.1.1/bin/phantomjs';
					$this->phantomDir='PHANTOMJS_EXECUTABLE='.dirname($_SERVER['DOCUMENT_ROOT']).'/node_modules/phantomjs/lib/phantom/bin/phantomjs';
					$this->casperFile=$_SERVER['DOCUMENT_ROOT'].'/js/casper.js';
					$this->casperDir=dirname($_SERVER['DOCUMENT_ROOT']).'/node_modules/casperjs/bin/casperjs';
					$year= date('Y');
					$this->pos=strpos($this->baseurl, 'year-');
					$this->baseurl=substr_replace($this->baseurl, $year, $this->pos+5,0);
					
				}
			}
			
		}
		
		$this->vendor_auth_username=$config['email'];
		
		$this->vendor_auth_password=$config['password'];
		$this->cookie_filename=dirname($_SERVER['DOCUMENT_ROOT']).'/tmp/'.$this->cookie_filename;
	}
	
	/**
	 * Trims from the start of a string till the postion of the character 
	 * @param string $string
	 * @param string $char
	 * @param integer $offset
	 */
	protected function str_trim($string,$char,$offset=null)
	{
		if($offset)
			$end=strrpos($string, $char);
		else 
			$end=strpos($string,$char);
		
		if($end !== false)
		{
			return substr_replace($string, "",0,$end+1);
		}
		return $string;
	}
	
	
	/**
	 * Trims the string from the character offset till the end of string
	 * @param string $string
	 * @param string $char
	 * @param integer $offset
	 */
	protected function str_trim_last($string,$char,$offset=null)
	{
		if($offset)
		{
			$start=strrpos($string, $char);
		}
		else
			$start=strpos($string,$char);
	
		if($start !== false)
		{
			return substr_replace($string, "",$start,strlen($string));
		}
		return $string;
			
	}
	
	/**
	 * Gets cookies and postfield data from the response
	 * @param mixed $response
	 * @param boolean $checkFlag
	 */
	public function getCookies($response,$checkFlag)
	{
		//Logic is indepepndent for every vendor and implemented in their individual class files
	}
	
	/**
	 * Validates user credentials, if login is successful returns true else returns false
	 * @param mixed $response
	 * @param boolean $checkFlag
	 */
	public function login($response,$checkFlag)
	{
		//Logic is indepepndent for every vendor and implemented in their individual class files	
	}
		
	/**
	 * 
	 * @param mixed $response
	 * @param boolean $checkFlag
	 * @return boolean|string
	 */
	function getOrders($response,$checkFlag)
	{
		if($this->vendor=='amazon')
		{
			$this->getNumofOrders($response);
		}
		$this->callback='parseOrders';
		//dd($this->orders);
		try {
			$result=$this->parseOrders($response,$checkFlag);
			//var_dump($this->orders);
			if($result==false){
				$message="No ".$this->vendor."orders found";
				return $message;
			}
			return $result;
		} catch (Exception $e) {
			Log::error(" Could not parse orders for ".$this->vendor);
			$message= "Could not process ".$this->vendor. " orders";
			return $message;
		}
	}
	
	/**
	 * Parses the response to get orders in a desired format and then save them.
	 * @param mixed $response
	 * @param boolean $checkFlag
	 */
	
	public function parseOrders($response,$checkFlag)
	{
		//Logic is indepepndent for every vendor and implemented in their individual class files
	}
	
	/**
	 * Validates user credentials for Amazon and returns login status and orders
	 * @return array
	 */
	public function checkLoginAmazon()
	{
		$checkFlag=false;
		ini_set('max_execution_time', 180);
		
		putenv($this->phantomDir);
	    $response=shell_exec("{$this->casperDir} {$this->casperFile} {$this->vendor_auth_username} {$this->vendor_auth_password} {$this->cookie_filename}");
		
		$dom= new \DOMDocument();
		@$dom->loadHTML($response);
		$loginError=$dom->getElementById('auth-error-message-box');
		
		if($loginError!==null)
		{
			
			$this->orders['status']=false;
			$this->orders['messages'][0] = 'Could not verify username/password. Try again Later.';
			return $this->orders;
		
		}
		else {
			$result=$this->getOrders($response,$checkFlag);
			//if(strstr($this->baseurl,date('Y')))
			$this->getPartialOrders($response,$checkFlag);
		
			$this->orders['status']=true;
			if(!$result)
			{
				$this->orders['messages'][0]= $result;
			}
			return $this->orders;
		}
		
		
	}
	
	/**
	 * Validates user credentials and returns login status and orders
	 * @return array
	 */
	public function checkLogin()
	{
		$checkFlag=false;
		$response=$this->doCurl($this->baseurl);
		if($response)
		{
			$this->getCookies($response,$checkFlag);
			$response=$this->doCurl($this->loginurl,true);
			$status= $this->login($response,$checkFlag);
			
			if($status)
			{
				$response=$this->doCurl($this->orderurl);
				$result=$this->getOrders($response,$checkFlag);
				
				$this->orders['status']=true;
				if(!$result)
				{
					$this->orders['messages'][0]= $result;
				}
				return $this->orders;
			}
			
		}
		$this->orders['status']=false;
		$this->orders['messages'][0] = 'Could not verify username/password. Try again Later.';
		return $this->orders;
	}
	
	/**
	 * Sets curl options for a given cURL handle
	 * @param  resource $myRequest
	 */
	
	public function setCurlOptions($myRequest)
	{
		
		if($this->agent != null)
		{
			curl_setopt($myRequest, CURLOPT_USERAGENT, $this->agent);
		}
		curl_setopt($myRequest,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($myRequest,CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($myRequest,CURLOPT_COOKIEFILE,$this->cookie_filename);
		curl_setopt($myRequest, CURLOPT_ENCODING , "gzip");
		curl_setopt($myRequest,CURLOPT_COOKIEJAR,$this->cookie_filename);
		
		curl_setopt($myRequest, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($myRequest,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($myRequest,CURLOPT_FOLLOWLOCATION,true);
		curl_setopt($myRequest, CURLOPT_AUTOREFERER, true );
		//	curl_setopt($myRequest, CURLOPT_VERBOSE, TRUE);
		//curl_setopt($myRequest, CURLOPT_HEADER,true);
		//curl_setopt($myRequest, CURLINFO_HEADER_OUT,true);
		curl_setopt($myRequest, CURLOPT_TIMEOUT,45);
		curl_setopt($myRequest, CURLOPT_MAXREDIRS, 10 );
		
		return $myRequest;
	}
	
	/**
	 * Does curl on the url provided and returns the response
	 * @param string $url
	 * @param boolean $login
	 * @return mixed
	 */
	protected function doCurl($url,$login=false)
	{
		
		$myRequest=curl_init($url);
		$myRequest=$this->setCurlOptions($myRequest);
		if($login)
		{
			curl_setopt($myRequest,CURLOPT_POST,TRUE);
			curl_setopt($myRequest,CURLOPT_POSTFIELDS,$this->postfields);
		}
		$response=curl_exec($myRequest);
		$info = curl_getinfo($myRequest);
		curl_close($myRequest);
		return $response;
	}
}
?>