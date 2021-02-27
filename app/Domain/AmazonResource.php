<?php namespace App\Domain;

use \Datetime;
use Bus;
use App\Commands\GetAllOrders;
use Log;

class AmazonResource extends VendorResource
{
	
	protected $count=-1;
	protected $startIndex=0;
	protected $orderNum;
	
	
		
	/**
	 * Gets cookies and checks if user login is successful returns true else returns false
	 * @param mixed $response
	 * @param boolean $checkFlag
	 * @return string|boolean
	 */	
	public function getCookies($response=null,$checkFlag)
	{
		ini_set('max_execution_time', 180);
		putenv($this->phantomDir);
	    $response=shell_exec("{$this->casperDir} {$this->casperFile} {$this->vendor_auth_username} {$this->vendor_auth_password} {$this->cookie_filename}");
	    
	    $dom= new \DOMDocument();
		@$dom->loadHTML($response);
		$loginError=$dom->getElementById('auth-error-message-box');
		
	  	if($loginError!==null)
	  	{
			if($checkFlag)
			{	
				Log::error(" Could not login ".$this->vendor);
				$message= "Could not process ".$this->vendor." orders.Please check your password.";
				return $message;
				
			}
	  	}	
		else 
		{
			$this->callback='getOrders';
			$result=$this->getOrders($response,$checkFlag);
			$this->getPartialOrders($response,$checkFlag);
			return $result;
			
		}
	}

	/**
	 * Gets the years for which the orders need to be fetched
	 * @param mixed $response
	 * @param boolean $checkFlag
	 */
	function getPartialOrders($response,$checkFlag)
	{
		$years=array();
		$dom= new \DOMDocument();
		@$dom->loadHTML($response);
		$tags=$dom->getElementsByTagName('select');
		foreach ($tags as $tag)
		{
			if($tag->getAttribute('name')=='orderFilter')
			{
				$childNodes=$tag->childNodes;
				foreach($childNodes as $node)
				{
					if(!(strstr($node->nodeValue,"last 30 days") || strstr($node->nodeValue,"past 6 months")||
						 strstr($node->nodeValue,date('Y'))))
						array_push($years,trim($node->nodeValue));
				}
				
			}
		}
		$this->consolidateOrders($years,$checkFlag);
	}
	
	
	/**
	 * Consolidates order urls for the years to get all orders
	 * @param array $years
	 * @param boolean $checkFlag
	 */
	function consolidateOrders($years,$checkFlag)
	{
		foreach ($years as $year)
		{
			if($checkFlag)
			{
				$this->count=-1;
				$copyThis=clone $this;
				$copyThis->orders=array(array());
				$copyThis->callback='getOrders';
				$pos=strpos($this->orderurl, 'year-');
				$copyThis->orderurl=substr_replace($copyThis->orderurl, $year, $pos+5,4);
				Bus::dispatch(new GetAllOrders($copyThis));
			}
			else
			{
				
				$pos=strpos($this->orderurl, 'year-');
				$this->orderurl=substr_replace($this->orderurl, $year, $pos+5,4);
				$response=$this->doCurl($this->orderurl);
				$this->getOrders($response,$checkFlag);
	
			}
			
		}
	}
		
	/**
	 * Gets no. of orders placed in a year.
	 * @param mixed $response
	 */
	function getNumofOrders($response)
	{
		$dom= new \DOMDocument();
		@$dom->loadHTML($response);
		
		$tags=$dom->getElementsByTagName('span');
		foreach($tags as $list)
		{
			if($list->getAttribute('class')=='num-orders')
				$this->orderNum=explode(' ', $list->nodeValue);
		}
	}
	
	/**
	 * Parses the response to get orders in a desired format and then save them.
	 * @param mixed $response
	 * @param boolean $checkFlag
	 */
	function parseOrders($response,$checkFlag)
	{
		$dom= new \DOMDocument();
		@$dom->loadHTML($response);
		$tags=$dom->getElementsByTagName('div');
		
		foreach($tags as $list)
		{
			
			if($list->getAttribute('class')=='a-column a-span3')
			{
				$this->count++;
				$item_count=1;
				$nodelist=$list->childNodes;
				$this->orders[$this->count]['vendor']=$this->vendor;
				$this->orders[$this->count]['configId']=$this->configId;
				$this->orders[$this->count]['date']=$nodelist->item(3)->nodeValue;
				$this->orders[$this->count]['date']=rtrim(ltrim($this->orders[$this->count]['date']));
				$date=DateTime::createFromFormat('j F Y', $this->orders[$this->count]['date']);
				$this->orders[$this->count]['date']=$date->format('D M d Y H:i:s O');
			
			}
			
			if($list->getAttribute('class')=='a-column a-span2')
			{
				
				$nodelist=$list->childNodes;
				$this->orders[$this->count]['paid']=trim($nodelist->item(3)->nodeValue);
				//$this->orders[$this->count]['paid']=$this->str_trim($this->orders[$this->count]['paid'], '.');
				$this->orders[$this->count]['paid']=str_replace("₹", '', $this->orders[$this->count]['paid']);
				$this->orders[$this->count]['paid']=str_replace(",", '', $this->orders[$this->count]['paid']);
			}
			if($list->getAttribute('class')=='a-fixed-right-grid-col actions a-col-right')
			{
			
				$nodelist=$list->childNodes;
				$this->orders[$this->count]['order_no']=$nodelist->item(1)->nodeValue;
				$this->orders[$this->count]['order_no']=$this->str_trim($this->orders[$this->count]['order_no'], '#');
				$this->orders[$this->count]['order_no']=ltrim($this->orders[$this->count]['order_no']);
				$this->orders[$this->count]['link']=$this->link.$this->orders[$this->count]['order_no'];
					
			}
			if($list->getAttribute('class')=='a-row shipment-top-row')
			{
					
				$nodelist=$list->childNodes;
				$string=$nodelist->item(1)->nodeValue;
				$this->orders[$this->count][$item_count]['shipment_status']=trim($string);
	
			}
			if($list->getAttribute('class')=='a-fixed-left-grid-col a-col-right')
			{
					
				$nodelist=$list->childNodes;
				$this->orders[$this->count][$item_count]['item']=trim($nodelist->item(1)->nodeValue);
				$item_count++;
				$this->orders[$this->count]['item_count']=$item_count;
			}
		}
	
		if($this->orderNum[0]>10)
		{
	
			$this->orderNum[0]=$this->orderNum[0]-10;
			$this->startIndex=$this->startIndex+10;
			$response=$this->doCurl($this->orderurl."&startIndex=".$this->startIndex);
			$this->parseOrders($response,$checkFlag);
		}
	
		$this->result=true;
		return $this->result;
	}
	
	
	/*function getCookies($response,$checkFlag)
	 {
	
	 $dom= new \DOMDocument();
	 @$dom->loadHTML($response);
	 $tags=$dom->getElementsByTagName('input');
	 $this->postfields=array();
	
	 foreach($tags as $list)
	 {
	 && $list->getAttribute('name')<>'showRmrMe' && $list->getAttribute('name')<>'accountStatusPolicy' )
	 	$this->postfields[$list->getAttribute('name')]=$list->getAttribute('value');
	 	}
	 		
	 	$this->postfields['email'] = $this->vendor_auth_username;
	 	$this->postfields['password'] = $this->vendor_auth_password;
	 	$this->postfields['create']='0';
	
	 	//	$this->postfields['metadata1']="O73kjihBoF67uhfT4jMqKjGuJ0kEP2wW6de/aTS0qdi+LjrWjaTGCPIoXuuz5Qe2s0cZkdIMNg/qrg6ZQwyg9sqQVXOy803AYih2maoAovS/mfbjHBycHGWNK4Eiht5blK2DDjNEILkwO5s38fBl1RDrLuncxwfnX6CHwwdoDrt6wcmGZCzNy0NBbc6/520wbULQK/8duV2rT4SH9JvAtC92D8tj9NxZxLkGc6TJHGp5RzAo7jazeivQjXX10RnKRzd3fdqYhbeV7bZ8XOSa8U/7MfUY+gStqA4f+dfcNDwPaY0IfsTWPD7IErAtLavbOn69ki3bPr0AkOHcTSQNN7mDsUaIWnwwrtn7C5vfIos4Re4TUTYAwzqRW66vaNE9pvoggKHUTYKrX07KRPDcICLkbIhMqJEvCM/QTG/WeOLy8TLT+iIREFixGuo0/P+wvEVA0/mTKP176EE4SjER6oUm/CRDj/cTwh9fe2jhm7BU8Ea7Eka4FpcU84kMVODOyd5v1A1E4oKKskjHZhBKHRaHfmM1BjqmwUYFax7Cb1+Wa8fbkfScN6hyEh0vKcmDaFePVe8dJNQf3zQfKIlIOBs1DxpIaKRw6BTMgCJp3V+s3yZSAJsxhQi+/KIb/ksK5bqqAvlWsgmLzClogIsWATKy3SkcI9przezuCVDMcUgDpw3UciAG5kqunsKjJ/8FBWPS+JIpYzXE1+QNW1ke4t9VrhBCxFeCwS51E/cyw4lC+751aKZzTSPPQz5Gr9t8Z3qsTrIzADJgJNd8/JSXORwdZB6PKgwc8sfMCx7Xo1E0yb+U4eJ2H6IhcW290gjoE91UvJd436JdEcd98RD7djcx/hb/dukHMlngPaqnVo/g7LtdD1B6kyLcDtSxHQ9GMX9bHpzM1dC7O0sEmYGlfG6buMZF/q4KCphe7P773YQUvc8iWpF9MbS1Z+3B3Cg0af5bRIhjQ9IbUmSiOz18oHMeYOs/H2r6dNastIo5obxPATlGYALNlTqpDwjCAMzd0KhMgw==";
	 	$this->postfields =http_build_query($this->postfields);
	 	if($checkFlag)
	 	{
	 	$this->callback='login';
	 	Bus::dispatch(new GetAllOrders($this));
	
	 	}
	 	}
	 	/**
	 	*
	 	* @param boolean $captcha
	 	* @param array $postfields
	 	*
	
	 function captchaLogin($captcha,$postfields)
	 {
	
	 	$this->postfields=$postfields;
	 	$this->postfields['guess']=$captcha;
	
	 	unset($this->postfields['showRmrMe']);
	 	unset($this->postfields['accountStatusPolicy']);
	 	$this->postfields =http_build_query($this->postfields);
	 	$response=$this->doCurl($this->loginurl,true);
	 	$status= $this->login($response,false);
	 		
	 }
	
	
	 function login($response,$checkFlag)
	  {
	  $dom= new \DOMDocument();
	  @$dom->loadHTML($response);
	  $tags=$dom->getElementsByTagName('input');
	  $this->postfields=array();
	
	  foreach($tags as $tag)
	  {
	  if($tag->getAttribute('type') == 'hidden'&&  $tag->getAttribute('name')<>'ue_back')
	  	$this->postfields[$tag->getAttribute('name')]=$tag->getAttribute('value');
	  	}
	  	$this->postfields['email'] = $this->vendor_auth_username;
	  	$this->postfields['password'] = $this->vendor_auth_password;
	  	$tags=$dom->getElementsByTagName('img');
	  	foreach($tags as $tag)
	  	{
	  	if($tag->getAttribute('id') == 'auth-captcha-image')
	  	{
	  	$result['image']=$dom->saveHTML($tag);
	  	$result['postfields']=$this->postfields;
	  	return $result;
	  	}
	  		
	  	}
	
	  	$loginError=$dom->getElementById('auth-error-message-box');
	  	if($loginError!==null)
	  	{
	  	if($checkFlag)
	  	{
	  	Log::error(" Could not login ".$this->vendor);
	  	$message= "Could not process".$this->vendor."orders";
	  	return $message;
	  	}
	  	return false;
	  	}
	  		
	  	else
	  	{
	
	  	if($checkFlag)
	  	{
	  	$this->callback='getOrders';
	  	$result=$this->getOrders($response,$checkFlag);
	  	$this->getPartialOrders($response,$checkFlag);
	  	return $result;
	  	}
	  		
	  	return true;
	  	}
	
	  	}*/
}

?>







		





