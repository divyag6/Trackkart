<?php namespace App\Domain;

use App\Domain\VendorResource;
use Carbon\Carbon;
use \Datetime;
use Bus;
use App\Commands\GetAllOrders;
use Log;

class JabongResource extends VendorResource
{

	protected $count=-1;

	/**
	 * Gets cookies and postfield data from the response
	 * @param mixed $response
	 * @param boolean $checkFlag
	 */
	
	function getCookies($response,$checkFlag)
	{
		
		$dom= new \DOMDocument();
		@$dom->loadHTML($response);
		$tags=$dom->getElementsByTagName('input');
		foreach ($tags as $list)
		{
			if($list->getAttribute('name') == 'footer_csrf')
				$this->token = $list->getAttribute('value');
		}
		$this->postfields =http_build_query(array('_csrf'=> $this->token,
				'isOc' => '0',
				'isGuest'=>'0',
				'validForm'=>'true',
				'email' => $this->vendor_auth_username,
				'password' => $this->vendor_auth_password,
		));
		
		if($checkFlag)
		{
			$this->callback='login';
			Bus::dispatch(new GetAllOrders($this));
		}
	}

	/**
	 * Checks if user login is successful returns true else returns false 
	 * @param mixed $response
	 * @param boolean $checkFlag
	 * @return string|boolean
	 */

	function login($response,$checkFlag)
	{
		$dom= new \DOMDocument();
		@$dom->loadHTML($response);
		$tags=$dom->getElementsByTagName('script');
		
		$ret=$this->str_trim($tags->item(0)->nodeValue, "'");
		$ret=$this->str_trim_last($ret,"'");
		$res=json_decode($ret, true);
		
		if($res['success']==0)	
		{
			
			if($checkFlag)
			{
				$this->callback='parseOrders';
				Log::error(" Could not login ".$this->vendor);
				$message= "Could not process ".$this->vendor." orders.Please check your password.";
				return $message;
			}
			return false;
		}	
			
		else
		{
			
			if($checkFlag)
			{
				$this->callback='getOrders';
				Bus::dispatch(new GetAllOrders($this));
			}
			return true;
		}
	}
	
	/**
	 * Consolidates order urls to get all Jabong orders 
	 * @param boolean $checkFlag
	 */
	function consolidateOrders($checkFlag)
	{
		
		$offset=$this->count-9;
		$offset=strval($offset);
		$replace=strval($this->count+1);
		$pos = strrpos($this->orderurl, $offset,-40);
	
		if($pos !== false)
		{
			$this->orderurl = substr_replace($this->orderurl, $replace, $pos, strlen($offset));
		}
		if($checkFlag)
		{
			$this->callback='getOrders';
			Bus::dispatch(new GetAllOrders($this));
		}
		else {
			$response=$this->doCurl($this->orderurl);
			$this->getOrders($response,$checkFlag);
		}
	}
	
	/**
	 * Parses the response to get orders in a desired format and then save them.
	 * @param mixed $response
	 * @param boolean $checkFlag
	 * @return boolean
	 */
	function parseOrders($response,$checkFlag)
	{
		
		$arr=json_decode($response, true);
		
		$dom= new \DOMDocument();
		@$dom->loadHTML($arr['html']);
		$tags=$dom->getElementsByTagName('div');
		
		foreach ($tags as $node){
			$item_count=1;
			if($node->getAttribute('class')=='order-dashboard-header clearfix')
			{
				
				$res=explode('order id ', $node->nodeValue);
				$res= explode('Placed on ',$res[1]);
				$this->count++;
				$this->orders[$this->count]['order_no']=$res[0];
				$res= explode('Total amount:',$res[1]);
				
				$this->orders[$this->count]['vendor']=$this->vendor;
				$this->orders[$this->count]['configId']=$this->configId;
				$this->orders[$this->count]['date']=$res[0];
				$date=DateTime::createFromFormat('D, jS M*y * g:iA',$this->orders[$this->count]['date']);
				$this->orders[$this->count]['date']=$date->format('D M d Y H:i:s O');
				
				$res= explode('order details',$res[1]);
				$this->orders[$this->count]['paid']=$res[0];
				$this->orders[$this->count]['link']=$this->link.$this->orders[$this->count]['order_no'];
			}
			
			if($node->getAttribute('class')=='product-headding')
			{
				$this->orders[$this->count][$item_count]['item']=$node->nodeValue;
				$this->orders[$this->count][$item_count]['shipment_status']='Failed transaction';
			}
			if($node->getAttribute('class')=='deliver-time')
			{
				$this->orders[$this->count][$item_count]['shipment_status']=$node->nodeValue;
			}
			$item_count++;
			$this->orders[$this->count]['item_count']= $item_count;
		
		}
		
		if($arr['misc']['ordersExhausted']==false)
		{
			$this->consolidateOrders($checkFlag);
		}
		$this->result=true;
		//dd($this->result);
		return $this->result;
	}
}

?>