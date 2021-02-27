<?php namespace App\Commands;

use App\Commands\Command;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use App\Domain\VendorResource;
use Log;

class GetAllOrders extends Command {

	public static $resources=array();
	public static $offset=-1;
	public static $message= array();
	public static $orders=array(array());
	
	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct($resources)
	{
		if(is_array($resources))
		{
			
			foreach ($resources as $resource)
			{
				array_push(self::$resources, $resource);
			}
			$this->getMultiOrders();
		}
		else {
			if(empty(self::$resources))
			{
				array_push(self::$resources, $resources);
				$this->getMultiOrders();
			}
			else 
			{
				array_push(self::$resources, $resources);
				//$this->getMultiOrders();
			}
		}
		
	}
	
	/**
	 * Parallely process various vendor configurations to get the orders
	 */
	
	public function getMultiOrders()
	{
		// make sure the rolling window isn't greater than the # of urls
		$rolling_window = 10;
		$message=0;
		//$rolling_window = (sizeof(self::$resources) < $rolling_window) ? sizeof(self::$resources) : $rolling_window;
		
		$mh = curl_multi_init();
		$curl_arr = array();
		
		// start the first batch of requests
		for ($i = 0; $i <sizeof(self::$resources); $i++) {
			
			if(self::$resources[$i]->callback=='getCookies')
				$myRequest[$i]=curl_init(self::$resources[$i]->baseurl);
			else if(self::$resources[$i]->callback=='login')
			{
				$myRequest[$i]=curl_init(self::$resources[$i]->loginurl);
				curl_setopt($myRequest[$i],CURLOPT_POST,TRUE);
				curl_setopt($myRequest[$i],CURLOPT_POSTFIELDS,self::$resources[$i]->postfields);
			}
			
			else if(self::$resources[$i]->callback=='getOrders')
			{
			
				$myRequest[$i]=curl_init(self::$resources[$i]->orderurl);
			}
		
			$myRequest[$i]=self::$resources[$i]->setCurlOptions($myRequest[$i]);
			curl_multi_add_handle($mh,$myRequest[$i]);
				
		}
		$running=null;
		do
		{
			$exec = curl_multi_exec($mh, $running);
			
		}while( $exec == CURLM_CALL_MULTI_PERFORM);
		
		while ($running && $exec == CURLM_OK)
		{
			do
			{
				$ret = curl_multi_exec($mh, $running);
			} while ($ret == CURLM_CALL_MULTI_PERFORM);
				
			while($done = curl_multi_info_read($mh))
			{
			
				$info = curl_getinfo($done['handle']);
				if ($info['http_code'] == 200 )
				{
					
					// request successful.  process output using the callback function.
					$output = curl_multi_getcontent($done['handle']);
					//echo "<br/>-------------header-------------";
					 //var_dump($info);
					for ($j=0;$j<$i;$j++)
					{
						if($myRequest[$j]==$done['handle'])
						{
							$callback=self::$resources[$j]->callback;
							self::$offset=$j;
							
							curl_close($myRequest[$j]);
							curl_close($myRequest[$j]);
							curl_multi_remove_handle($mh, $done['handle']);
							$response=self::$resources[$j]->$callback($output,true);
							
							if(is_string($response))
							{
								if(empty(self::$orders[0][0]))
								{
									$arr['messages'][$message]=$response;
									self::$orders=$arr;
								}
								else
									self::$orders['messages'][$message]= $response;
								$message++;
							}
							
					 		//combine retreived orders
					 		if(self::$resources[$j]->callback=='parseOrders' )
					 		{
					 			
					 			if($response==1 && !empty(self::$resources[$j]->orders[0]))
					 			{
					 				if(empty(self::$orders[0]) && !isset(self::$orders['messages']))
					 					self::$orders= self::$resources[$j]->orders;
					 				else
					 					self::$orders=array_merge(self::$orders,self::$resources[$j]->orders);
					 			}
					 		}
						}
					
					}
					
					// start a new request (it's important to do this before removing the old one)
					
					while(sizeof(self::$resources) > $i)
					{
						if(self::$resources[$i]->callback=='getCookies')
						{
							$myRequest[$i]=curl_init(self::$resources[$i]->baseurl);
						}
						
						else if(self::$resources[$i]->callback=='login')
						{
							$myRequest[$i]=curl_init(self::$resources[$i]->loginurl);
							curl_setopt($myRequest[$i],CURLOPT_POST,TRUE);
							curl_setopt($myRequest[$i],CURLOPT_POSTFIELDS,self::$resources[$i]->postfields);
						}
						
						else if(self::$resources[$i]->callback=='getOrders')
						{
							$myRequest[$i]=curl_init(self::$resources[$i]->orderurl);
						}
							
						
						$myRequest[$i]=self::$resources[$i]->setCurlOptions($myRequest[$i]);
						curl_multi_add_handle($mh,$myRequest[$i]);
						
						$i++;
					}
					
						do
						{
							$exec = curl_multi_exec($mh, $running);
						} while ($exec == CURLM_CALL_MULTI_PERFORM);
					
					
				}
				else
				{
					// remove the curl handle that just completed
					curl_close($done['handle']);
					curl_close($done['handle']);
					curl_multi_remove_handle($mh, $done['handle']);
								
					
					// request failed.  add error handling.
					for ($j=0;$j<$i;$j++)
					{
						if($myRequest[$j]==$done['handle'])
						{
							Log::error("<".$info['http_code']."> Could not process ".self::$resources[$j]->vendor." orders.Callback--".self::$resources[$j]->callback);
							self::$orders['messages'][$message]= "Could not process ".self::$resources[$j]->vendor." orders";
							$message++;
						}
					}
				}
				
			}//end while that runs if there is some info.
			
		}//end of while that exits when running=0
		
		curl_multi_close($mh);
		return self::$orders;
	}
	

}
