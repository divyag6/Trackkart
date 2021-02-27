<?php namespace App\Domain;

use App\Domain\VendorResource;
use \Datetime;

class FirstcryResource extends VendorResource
{
	protected $webDriver;
	
	function getCookies()
	{
		
		/*$c=curl_init($this->baseurl);
		$myRequest=$this->setCurlOptions($c);
		curl_setopt($myRequest, CURLOPT_ENCODING , "gzip");
		$page = curl_exec($myRequest);
		curl_close($myRequest);*/
		print_r($this);
		

	}
	function getBrowserCookies()
	{
		$capabilities = array(\WebDriverCapabilityType::BROWSER_NAME => 'firefox');
		$this->webDriver = \RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities,80000);
		$this->webDriver->get($this->baseurl);
		$cookies=$this->webDriver->manage()->getCookies();
		return $cookies;
	}
	
	function setCookies($loginCookies)
	{
		$cookies= $this->getBrowserCookies();
		
		$file=fopen($this->cookie_filename,'a') or die("can't open file");
		foreach ($cookies as $cookie)
		{
			if($cookie['domain']==='www.firstcry.com')
			{
				$string=$cookie['domain']."\tFALSE\t".$cookie['path']."\tFALSE\t".(isset($cookie['expiry'])?$cookie['expiry']:"")."\t".$cookie['name']."\t".$cookie['value'].PHP_EOL;
			}
			else
			{
				$string=$cookie['domain']."\tTRUE\t".$cookie['path']."\tFALSE\t".(isset($cookie['expiry'])?$cookie['expiry']:"")."\t".$cookie['name']."\t".$cookie['value'].PHP_EOL;
			}
			//print_r($cookie['domain']);
			//echo $string;
			fwrite($file,$string);
		}
		
		
			$expiration=time()+7*60*60;
			$expiryLoged=time()+1*60*60;
			$auth= ".firstcry.com\tTRUE\t/\tFALSE\t".$expiration."\tFC_AUTH\t".$loginCookies['AuthenticateUserResult']['AUTH'];
			$email=".firstcry.com\tTRUE\t/\tFALSE\t".$expiration."\t_\$FC_LoginInfo\$_\t".$loginCookies['AuthenticateUserResult']['EmailID'];
			$userId= ".firstcry.com\tTRUE\t/\tFALSE\t".$expiration."\t_\$FC_UserInfo\$_\t".$loginCookies['AuthenticateUserResult']['UserId'];
			$isLoged= ".firstcry.com\tTRUE\t/\tFALSE\t".$expiryLoged."\tisloged\ttrue";
			$pdate= ".firstcry.com\tTRUE\t/\tFALSE\tpdate\t4%2F28%2F2014%2010%3A11%3A14%20PM";
			$product= ".firstcry.com\tTRUE\t/\tFALSE\t".$expiration."\tProductViewed\t74954%2C279906%2C381947%2C449243%2C511409";
			$aksb = "www.firstcry.com\tFALSE\t/\tFALSE\tAKSB\ts=1431595654456&r=http%3A//www.firstcry.com/";
				
			fwrite($file, $auth.PHP_EOL.$email.PHP_EOL.$userId.PHP_EOL.$isLoged.PHP_EOL.$pdate.PHP_EOL.$product.PHP_EOL.$aksb);
			
			fclose($file);
		
	}
	
	function getOrders()
	{
		
			
		$myRequest=curl_init($this->orderurl);
		//$myRequest=$this->setCurlOptions($myRequest);
		if($this->agent != null)
		{
			curl_setopt($myRequest, CURLOPT_USERAGENT, $this->agent);
		}
		curl_setopt($myRequest,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($myRequest,CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt($myRequest,CURLOPT_COOKIEFILE,$this->cookie_filename);
		
		//curl_setopt($myRequest,CURLOPT_COOKIEJAR,$this->cookie_filename);
		curl_setopt($myRequest,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($myRequest,CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($myRequest, CURLOPT_AUTOREFERER, true );
		curl_setopt($myRequest, CURLOPT_VERBOSE, TRUE);
		
		//curl_setopt($myRequest, CURLOPT_COOKIESESSION, true );
		
		curl_setopt($myRequest, CURLOPT_TIMEOUT, 60 );
		curl_setopt($myRequest, CURLOPT_MAXREDIRS, 10 );
		
		curl_setopt($myRequest, CURLOPT_ENCODING , "gzip");
		$orderResponse=curl_exec($myRequest);
		//echo $orderResponse;
		$statusCode = curl_getinfo($myRequest, CURLINFO_HTTP_CODE);
		//echo $statusCode;
		//curl_close($myRequest);
		return $orderResponse;
	}
	
	function loginFirstcry()
	{
		
		$this->getCookies();
		
		$login=curl_init($this->loginurl);
		$myRequest=$this->setCurlOptions($login);
		$postfields =array('Email' => urlencode($this->vendor_auth_username),
											'Password'=>$this->vendor_auth_password,
											'RememberMe'=>'false',
											'SiteType' => 0,
											'ValidateOnly'=>'true');
		$postfields=json_encode($postfields);
		
		curl_setopt($myRequest, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
														  'Connection: Keep-Alive'));
		curl_setopt($myRequest,CURLOPT_POST,TRUE);
		curl_setopt($myRequest,CURLOPT_POSTFIELDS,$postfields);
		curl_setopt($myRequest, CURLOPT_ENCODING , "gzip");
		
		$response = curl_exec($myRequest);
		//echo $response;
		curl_close($myRequest);
		
		
		$loginError=json_decode($response,true);
		
		
		if($loginError['AuthenticateUserResult']['AUTH']!==null){
			
			$this->setCookies($loginError);
			return true;
		}
		else{
			echo "false";
			return false;
		}
			
	
		
		
	}
	


	function parseOrders()
	{
		$count=-1;
		$item_count=1;
		$orderresponse=$this->getOrders();
		//echo $orderresponse;
		$dom= new \DOMDocument();
		@$dom->loadHTML($orderresponse);
		$tags=$dom->getElementsByTagName('li');
		foreach($tags as $nodelist)
		{
			if($nodelist->getAttribute('class')=='ls1 bld show_ord')
			{
				$count++;
				$orderNos[$count]=$nodelist->nodeValue;
			
			}	
			
		}
		foreach ($orderNos as $orderNo)
		{
		
			$this->orderurl="http://www.firstcry.com/OrderDetails?poid=".$orderNo;
			$detailResponse=$this->getOrders();
			echo $detailResponse;
		}
	}
}

?>