<?php namespace App\Domain;

use App\Domain\VendorResource;
use \Datetime;
use Bus;
use App\Commands\GetAllOrders;
use Log;
use Exception;

class FabfurnishResource extends VendorResource
{
	
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
			if($list->getAttribute('name') == 'YII_CSRF_TOKEN')
			{
				$this->token=  $list->getAttribute('value');
			}
		}
		
		$this->postfields =http_build_query(array('LoginForm[email]' => $this->vendor_auth_username,
			'LoginForm[password]' => $this->vendor_auth_password,
			'LoginForm[rememberme]'=>'1',
			'LoginForm[protocol]'=>"",
			'LoginForm[wishListUrl]'=>"",
			'YII_CSRF_TOKEN'=>$this->token,
			'YII_CSRF_TOKEN'=> $this->token));
		
			if($checkFlag)
			{
				$this->callback='login';
				Bus::dispatch(new GetAllOrders($this));
			}
			return true;
	}
	
	/**
	 * Checks if user login is successful returns true else returns false
	 * @param mixed $response
	 * @param boolean $checkFlag
	 * @return boolean|string
	 */
	function login($response,$checkFlag)
	{
		$dom= new \DOMDocument();
		@$dom->loadHTML($response);
		$classname = 's-error msg';
		$finder = new \DomXPath($dom);
		$loginError = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");
	
		if($loginError->length===0)
		{
			if($checkFlag)
			{
				$this->callback='getOrders';
				Bus::dispatch(new GetAllOrders($this));
			}
			
			return true;
		}
		else
		{
			if($checkFlag)
			{
				Log::error(" Could not login ".$this->vendor);
				$message= "Could not process ".$this->vendor." orders.Please check your password.";
				return $message;
			}
			return false;
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
			$count=0;
			
			$dom= new \DOMDocument();
			@$dom->loadHTML($response);
			$tags= $dom->getElementsByTagName('ul');
			
			foreach($tags as $ordertag )
			{
				if($ordertag->getAttribute('class')=='myOrderNewConHeader myOrderHighlight' ||
						$ordertag->getAttribute('class')=='myOrderNewConHeader ')
				{
					$nodelist=$ordertag->childNodes;
					
					$this->orders[$count]['vendor']=$this->vendor;
					$this->orders[$count]['configId']=$this->configId;
					$this->orders[$count]['order_no']=$nodelist->item(0)->nodeValue;
					$this->orders[$count]['link']=$this->link.$this->orders[$count]['order_no'];
					
					$this->orders[$count]['date']=$nodelist->item(2)->nodeValue;
					$date=DateTime::createFromFormat('jS M, y', $this->orders[$count]['date']);
					$this->orders[$count]['date']=$date->format('D M d Y H:i:s O');
			
					$this->orders[$count]['paid']=$nodelist->item(6)->nodeValue;
					$this->orders[$count]['paid']=$this->str_trim($this->orders[$count]['paid'], '.');
					$this->orders[$count]['paid']=str_replace(',', '', $this->orders[$count]['paid']);
					
					$itemlist=$nodelist->item(4)->childNodes;
					for ($item_count=1 ;$item_count<$itemlist->length;$item_count++ ){
							
						$item_details=$itemlist->item($item_count)->childNodes;
						$this->orders[$count][$item_count]['item']=$item_details->item(0)->nodeValue;
						$this->orders[$count][$item_count]['item_price']=$item_details->item(2)->nodeValue;
						$this->orders[$count][$item_count]['shipment_status']=$item_details->item(4)->nodeValue;
					}
					$this->orders[$count]['item_count']=$itemlist->length ;
					
					$count++;
				}
			}
			$this->result=true;
			return $this->result;
	}
}

?>