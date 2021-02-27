<?php namespace App\Domain;

use App\Domain;
use App;
use App\VendorConfig;
use Auth;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Flysystem\Plugin\GetWithMetadata;
use Bus;
use App\Commands\GetAllOrders;
use Queue;
use SplQueue;
use Illuminate\View\View;

//include_once 'orders.html';
//$id = isset($_POST['vendor']) ? $_POST['vendor'] : '';
//echo $id;
class OrderRepository
{

	public $resource=array();
	protected $urls;
	protected static $orders=array(array());

	/**
	 * Create a new instance of vendor configurations and store in an array
	 * @param Request $configs
	 */
	function __construct($configs)
	{
		
		$xml=simplexml_load_file($_SERVER['DOCUMENT_ROOT'].'/VendorURL.xml')or die("Error: Cannot create object");
		$i=0;
		
		foreach ($configs as $config){
			
			foreach ($xml->vendor as $value)
			{
				if ($config['vendor_id']==$value->attributes())
				{
					$this->resource[$i]=app()->make("$value->vendorname",[$config]);
					if(file_exists($this->resource[$i]->cookie_filename) && $this->resource[$i]->vendor != 'amazon')
					{
						unlink($this->resource[$i]->cookie_filename);
					}
				}
				
			}
			
		$i++;		
		}
		
	}
	
	/**
	 * Dispatches cURL jobs to fetch orders
	 * @return array
	 */
	public function dispatchCurlJobs()
	{
		$response=Bus::dispatch(new GetAllOrders($this->resource));
		return $response;
	}
	
	
	
}
?>


