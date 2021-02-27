<?php namespace App\Domain;

use App\Domain\VendorResource;
use \Datetime;
use App\Commands\GetAllOrders;
use Bus;
use Log;

class PepperfryResource extends VendorResource
{
	protected $pageNo=2;
	protected $count=-1;
	
	/**
	 * Gets cookies and postfield data from the response
	 * @param mixed $response
	 * @param boolean $checkFlag
	 */
	public function getCookies($response,$checkFlag)
	{
		$ch=curl_init('http://www.pepperfry.com/site_page/getCsrfToken');
		$ch=$this->setCurlOptions($ch);
		$token=curl_exec($ch);
	
		curl_close($ch);
		$this->postfields =http_build_query(array('user[new]' =>$this->vendor_auth_username,
				'password' =>$this->vendor_auth_password
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
	 */
	public function login($response,$checkFlag)
	{
		$loginError = json_decode($response);
		if(str_contains($response,'login_error'))
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
	 * Consolidates order urls to get all Pepperfry orders
	 * @param boolean $checkFlag
	 */
	function consolidateOrders($checkFlag)
	{
		$pos = strrpos($this->orderurl,'=');
		$replace=strval($this->pageNo);
	
		if($pos !== false)
		{
			$this->orderurl = substr_replace($this->orderurl, $replace, $pos+1, strlen($this->orderurl));
		}
		else 
		{
			$this->orderurl = $this->orderurl."?p=".$replace;
		}
		$this->pageNo++;
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
	public function parseOrders($response,$checkFlag)
	{	
		
		$item_count=1;
		
		$dom= new \DOMDocument();
		@$dom->loadHTML($response);
		
		$tags=$dom->getElementsByTagName('div');
		foreach ($tags as $node)
		{
			$class= $node->getAttribute('class');
			if($class=='orders-placed-counter')
			{
				$orders=trim($node->nodeValue);
				$orders=explode(" ", $orders);
				$totalOrders=intval($orders[3]);
				
			}
			if($class=='order-row order-header-row')
			{
			
				$this->count++;
				$item_count=1;
				$nodeList=$node->childNodes;
				$this->orders[$this->count]['vendor']=$this->vendor;
				$this->orders[$this->count]['configId']=$this->configId;
				$this->orders[$this->count]['link']=$this->link;
				$this->orders[$this->count]['status']=$nodeList->item(1)->nodeValue;
				//$this->orders[$this->count]['status']=$this->str_trim($this->orders[$this->count]['status'],'.');
				$this->orders[$this->count]['status']=ucwords(strtolower($this->orders[$this->count]['status']));
				
			}
			
			if($class=='order-row order-subheader-row')
			{
					$details=$node->childNodes;
					
					$nodeList=$details->item(1)->childNodes;
					
					$this->orders[$this->count]['date']=$nodeList->item(3)->nodeValue;
					$this->orders[$this->count]['date']=substr_replace($this->orders[$this->count]['date'], "",-13);
					$date=DateTime::createFromFormat('jS F Y', $this->orders[$this->count]['date']);
					$this->orders[$this->count]['date']=$date->format('D M d Y H:i:s O');
				
					
					$this->orders[$this->count]['paid']=$nodeList->item(7)->nodeValue;
					$this->orders[$this->count]['paid']=$this->str_trim($this->orders[$this->count]['paid'],'.');
					
					if((strpos($this->orders[$this->count]['status'], 'Cancel')) || (strpos($this->orders[$this->count]['status'], 'Refund')) !== false)
					{
						$this->orders[$this->count]['refund']=$nodeList->item(9)->nodeValue;
					}
			
			
					
					$nodeList=$details->item(5)->childNodes;
					
					$this->orders[$this->count]['order_no']=$nodeList->item(1)->nodeValue;
					$this->orders[$this->count]['order_no']=trim($this->str_trim($this->orders[$this->count]['order_no'],'.'));
				
				}
				
				if($class=='order-row order-product-row')
				{
					
					$article=$node->childNodes;
					
					$this->orders[$this->count][$item_count]['item']=$article->item(1)->nodeValue;
					$nodeList=$article->item(5)->childNodes->item(1)->childNodes;
					
					$this->orders[$this->count][$item_count]['sku']=$nodeList->item(1)->nodeValue;
					$this->orders[$this->count][$item_count]['qty']=$nodeList->item(7)->nodeValue;
					$this->orders[$this->count][$item_count]['qty']=$this->str_trim($this->orders[$this->count][$item_count]['qty'],':');
					
					if($nodeList->item(9)!==null)
					{
						$this->orders[$this->count][$item_count]['shipment_status']=ucwords(strtolower($nodeList->item(9)->nodeValue));
						
						if (strstr($this->orders[$this->count][$item_count]['shipment_status'], 'Cancel') === false)
						{
							if (strstr($this->orders[$this->count][$item_count]['shipment_status'], 'Status') !== false)
							{
								$this->orders[$this->count][$item_count]['shipment_status']=$this->str_trim($this->orders[$this->count][$item_count]['shipment_status'],':');
							}
							else {
								$this->orders[$this->count][$item_count]['shipment_status']="Delivered";
							}
							
								
							$this->orders[$this->count][$item_count]['shipped_on']=$nodeList->item(11)->nodeValue;
							if($nodeList->item(13)!==null && strstr($nodeList->item(13)->nodeValue,'Shipped By')!==false){
									$this->orders[$this->count][$item_count]['shipped_by']=$nodeList->item(13)->nodeValue;
							}
						}
						if($nodeList->item(17)!==null){
							$this->orders[$this->count][$item_count]['shipment_status']=ucwords(strtolower($nodeList->item(17)->nodeValue));
						}
					}
					else {
						$this->orders[$this->count][$item_count]['shipment_status']='Not yet dispatched';
					}
					
					$item_count++;
					$this->orders[$this->count]['item_count']= $item_count;
					$qty=0;
					for($i=1;$i<$item_count;$i++){
						$qty=$qty+ intval($this->orders[$this->count][$i]['qty']);
					}
					$this->orders[$this->count]['qty']=$qty;
				}
			}
			if($totalOrders>$this->count+1)
			{
				$this->consolidateOrders($checkFlag);
			}
			$this->result=true;
			return $this->result;
		
		}	
}
?>