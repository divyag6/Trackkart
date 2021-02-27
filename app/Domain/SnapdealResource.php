<?php namespace App\Domain;

use App\Domain\VendorResource;
use \Datetime;
use Bus;
use App\Commands\GetAllOrders;
use Log;

class SnapdealResource extends VendorResource
{
	protected $repeat=1;
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
			if($list->getAttribute('name') == 'CSRFToken')
				$this->token = $list->getAttribute('value');
		}
		
		$this->postfields =http_build_query(array('j_username' => $this->vendor_auth_username,
				'j_password' => $this->vendor_auth_password,
				'_spring_security_remember_me'=>'1',
				'ajax'=>'true'));
		
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
	 * 
	 */
	function login($response,$checkFlag)
	{
		$loginError = json_decode($response,true);
		if($loginError['status']=="fail")
		{
			if($checkFlag)
			{
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
	 * Consolidates order urls to get all Snapdeal orders.
	 * @param string $count
	 * @param boolean $checkFlag
	 */
	function consolidateOrders($count,$checkFlag)
	{
		if($count!=='1')
			$this->orderurl=$this->str_trim_last($this->orderurl,'=',1);
		$this->orderurl=$this->orderurl.'='.$count;
		if($checkFlag)
		{
			$this->callback='getOrders';
			Bus::dispatch(new GetAllOrders($this));
		}
		else
		{
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
		
		$item_count=1;
		
		$dom= new \DOMDocument();
		@$dom->loadHTML($response);
		$scriptTags=$dom->getElementsByTagName('script');
		foreach($scriptTags as $tag)
		{
			if(strstr($tag->nodeValue,"true"))
			{
				$this->repeat++;
				$this->consolidateOrders($this->repeat,$checkFlag);
			}
		}
		$tags=$dom->getElementsByTagName('div');
		
		foreach($tags as $list)
		{
			//echo $list->getAttribute('class')."<br>";
			if($list->getAttribute('class')=='orderId')
			{
				$this->count++;
				$item_count=1;
				$this->orders[$this->count]['vendor']=$this->vendor;
				$this->orders[$this->count]['configId']=$this->configId;
				$nodeList=$list->childNodes;
				foreach ($nodeList as $node)
				{
				
					if($node->nodeName=='span')
						$aTags=$node->childNodes;
				}
				foreach ($aTags as $node)
				{
				
					if($node->nodeName=='a')
						$link=$node->getAttribute('href');
				}
				
				$this->orders[$this->count]['order_no']=$this->str_trim($list->nodeValue,':');
				$this->orders[$this->count]['order_no']=$this->str_trim_last($this->orders[$this->count]['order_no'],'(');
				$this->orders[$this->count]['qty']=$this->str_trim($list->nodeValue,'(');
				$this->orders[$this->count]['qty']=$this->str_trim_last($this->orders[$this->count]['qty'],' ');
				$this->orders[$this->count]['link']=$this->link.$link;
				
				if($this->orders[$this->count]['qty']==0)
				{
					$this->orders[$this->count][$item_count]['item']='Not defined';
					$this->orders[$this->count][$item_count]['shipment_status']='Not defined';
					$item_count++;
					$this->orders[$this->count]['item_count']=$item_count;
				}
				
			}
			if($list->getAttribute('class')=='ordDate')
			{
				$this->orders[$this->count]['date']=trim($this->str_trim($list->nodeValue,'n '));
				$date=DateTime::createFromFormat('d M, Y', $this->orders[$this->count]['date']);
				$this->orders[$this->count]['date']=$date->format('D M d Y H:i:s O');
				
			}	
			if($list->getAttribute('class')=='ordContent')
			{
				$this->orders[$this->count][$item_count]['item']=trim($this->str_trim_last($list->nodeValue,'Return'));
	
			}
			if($list->getAttribute('class')==' sd-tour trackingDetails   bottomBorderTrack')
			{
				//dd($list->nodeValue);
				$this->orders[$this->count][$item_count]['shipment_status']=trim($this->str_trim($list->nodeValue,':'));
				
				$pos = strpos($this->orders[$this->count][$item_count]['shipment_status'],'Delivered');
				
				if ($pos !== false) {
					$this->orders[$this->count][$item_count]['shipment_status'] = substr_replace($this->orders[$this->count][$item_count]['shipment_status'],"",$pos,strlen('Delivered'));
				}
				
				$item_count++;
				$this->orders[$this->count]['item_count']=$item_count;
			}
			
		}
		$this->result=true;
		return $this->result;
		
	}
}
	


		
