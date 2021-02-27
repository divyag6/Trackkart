<?php namespace App\Domain;

use App\Domain\VendorResource;
use Carbon\Carbon;
use \Datetime;
use Bus;
use App\Commands\GetAllOrders;
use PhpParser\Node\Stmt\Foreach_;
use Log;
use phpDocumentor\Reflection\DocBlock\Tag\VarTagTest;

class FlipkartResource extends VendorResource
{
	protected $repeat=0;
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
		$tags=$dom->getElementsByTagName('script');
		
		foreach ($tags as $list)
		{
			$string=$list->nodeValue;
			
			if(strpos($string,'window.__FK')!==FALSE)
			{
				$string=$this->str_trim($string,'"');
				$string=$this->str_trim_last($string, '"');
				$this->token=$string;
				break;
			}
		}
		$this->postfields =http_build_query(array('__FK'=> $this->token,
				'contact_id' => $this->vendor_auth_username,
				'password' => $this->vendor_auth_password
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
		$nodeList=$dom->getElementsByTagName('body');
	
		if(stristr(($nodeList->item(0)->nodeValue),"ok")===FALSE)
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
	 * Consolidates order urls to fetch all orders
	 * @param integer $count
	 * @param boolean $checkFlag
	 */
	function consolidateOrders($count,$checkFlag)
	{
		
		if($count!==5)
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
		
		$arr= array();
		$item_count=1;
		$flag=1;
		$dom= new \DOMDocument();
		
		if(strstr($this->orderurl,'inf-start'))
		{
		
			$arr= json_decode($response,true);
	
		
			if($arr['count']!==0 && $arr['count']!=null)
			{
				@$dom->loadHTML($arr['html']);
				$this->repeat=$this->repeat+5;
				//$this->consolidateOrders($this->repeat,$checkFlag);
			}
			else
				$flag=0;
		}
		else 
		{
			@$dom->loadHTML($response);
			$this->orderurl=$this->orderurl.'&inf-start';
			$this->repeat=$this->repeat+5;
			//$this->consolidateOrders($this->repeat,$checkFlag);
		}
		if($flag)
		{
			$tags=$dom->getElementsByTagName('div');
			foreach($tags as $list)
			{	
				if($list->getAttribute('class')=='unit size1of5')
				{
					
					$this->count++;
					$item_count=1;
				
					
					$this->orders[$this->count]['vendor']=$this->vendor;
					$this->orders[$this->count]['configId']=$this->configId;
					$order_no=trim($list->nodeValue);
					$this->orders[$this->count]['order_no']=$order_no;
					$pos=strpos($this->link,'=');
					$this->orders[$this->count]['link'] = substr_replace($this->link, $order_no, $pos+1, 0);
					
				}
				if($list->getAttribute('class')=='line order-item-inner')
				{
					$nodelist=$list->childNodes;
					$item_details=$nodelist->item(3)->childNodes;
					$this->orders[$this->count][$item_count]['item']=$item_details->item(1)->nodeValue;
					if(strpos($item_details->item(3)->nodeValue, 'Qty'))
					{
						$this->orders[$this->count][$item_count]['qty']=$item_details->item(3)->nodeValue;
					}
					else {
						$this->orders[$this->count][$item_count]['qty']=$item_details->item(5)->nodeValue;
						$this->orders[$this->count][$item_count]['info']=$item_details->item(3)->nodeValue;
					}
					
					$this->orders[$this->count][$item_count]['qty']=$this->str_trim($this->orders[$this->count][$item_count]['qty'],':',1);
					
					$this->orders[$this->count][$item_count]['item_price']=$nodelist->item(5)->nodeValue;
					if(strpos($this->orders[$this->count][$item_count]['item_price'], 'OFFERS'))
					{
						$this->orders[$this->count][$item_count]['item_price']=$this->str_trim_last($this->orders[$this->count][$item_count]['item_price'], 'OFFERS');
					}
					$string=$nodelist->item(7)->nodeValue;
					
					$words=str_word_count($string, 1);
					
					$this->orders[$this->count][$item_count]['shipment_status']=$string;
					//$this->orders[$this->count][$item_count]['delivered_on']=$this->str_trim($string,'n ');
					$item_count++;
					
				}
			
				if($list->getAttribute('class')=='line order-total')
				{
					$nodelist=$list->childNodes;
					$nodes=$nodelist->item(1)->childNodes;
					
					$this->orders[$this->count]['date']=$this->str_trim($nodes->item(1)->nodeValue,':',1);
					$this->orders[$this->count]['date']=str_replace("'", " ", $this->orders[$this->count]['date']);
					$date=DateTime::createFromFormat(' D, dS M y ', $this->orders[$this->count]['date']);
					$this->orders[$this->count]['date']=$date->format('D M d Y H:i:s O');
					
					$this->orders[$this->count]['paid']=$nodelist->item(1)->nodeValue;
					$this->orders[$this->count]['paid']=$this->str_trim($this->orders[$this->count]['paid'],'.');
					$this->orders[$this->count]['item_count']= $item_count;
				}
			}
			
			$this->consolidateOrders($this->repeat,$checkFlag);
		}
		
		$this->result=true;
		return $this->result;
	}
	
}

?>