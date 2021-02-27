<?php namespace App\Domain;

class LoginBroker
{
	
	protected $resource;
	
	/**
	 * Create a new instance of the vendor configuartion
	 * @param Request $config
	 */
	function __construct($config)
	{
		$xml=simplexml_load_file($_SERVER['DOCUMENT_ROOT'].'/VendorURL.xml')or die("Error: Cannot create object");
		$i=0;
		foreach ($xml->vendor as $value)
		{
			if ($config->vendor_id==$value->attributes())
			{
				$this->resource=app()->make("$value->vendorname",[$config]);
			}
		}
				
	}
	
	/**
	 * Validates user credentials and returns login status and orders
	 * @param string $captcha
	 * @param string $postfields
	 * @return array
	 */
	public function checkLogin($captcha=null,$postfields=null)
	{
		if($this->resource->vendor == 'amazon')
		{
			return $this->resource->checkLoginAmazon();
		}
		else 
		{
			if(file_exists($this->resource->cookie_filename))
			{
				unlink($this->resource->cookie_filename);
			}
			return $this->resource->checkLogin();
		}	
	}
}

?>