<?php namespace App\Handlers\Commands;

use App\Commands\GetAllOrders;

use Illuminate\Queue\InteractsWithQueue;
use App\Domain\VendorResource;

class GetAllOrdersHandler {

	protected $orders=array(array());
	/**
	 * Create the command handler.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//
	}

	/**
	 * Handle the command.
	 *
	 * @param  GetAllOrders  $command
	 * @return void
	 */
	public function handle(GetAllOrders $command)
	{
		/*if($command::$offset==-1){
			return $command::$orders;
		}
		else 
		{
			if($command::$resources[$command::$offset]->callback=='parseOrders' && $command::$resources[$command::$offset]!==NULL)
			{
				return $command::$orders;
			}
		}	*/
		return $command::$orders;
	}
		
		
	

}
