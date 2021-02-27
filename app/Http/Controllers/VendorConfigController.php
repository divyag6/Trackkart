<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Hash;
use Illuminate\Http\Request;
use App\VendorConfig;
use Illuminate\Http\Response;
use Illuminate\Support\Facade;
//use Illuminate\Support\Facades\Auth;
use App\Vendor;
use App\User;
use App\Orders;
use Auth;
use App\Http\Requests\VendorConfigRequest;
use Input;
use Crypt;
use App\Domain\LoginBroker;
use App\Http\Controllers\ApiController;
Use Validator;
use JWTAuth;

class VendorConfigController extends ApiController {

	protected $user; 
	
	
	public function  __construct()
	{
		
		$token = JWTAuth::getToken();
		$this->user = JWTAuth::toUser($token);
		//$this->user= Auth::User();
		//$this->middleware('jwt.auth');
		//$this->middleware('jwt.refresh');
	}
	
	/**
	 * Fetches all the vendor configurations
	 *
	 * @return Response
	 */
	public function index()
	{
		$vendors=array(array());
	
		$configs=VendorConfig::where('user_id','=',$this->user->id)->get();
		if(!$configs->isEmpty())
		{
			foreach ($configs as $config)
			{
				$vendor=Vendor::findOrFail($config->vendor_id, $columns = array('name'));
				$config->name=$vendor->name;
				
			}
			
			return $this->setStatusCode(200)->respond([
					'data' => $configs
			]);
		}
		else
		{
			return $this->respondNotFound('No vendor configurations');
		}
	}

	 /* Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create()
	{
		$vendor=Vendor::lists('name','id');
		return $this->setStatusCode(200)->respond([
				'data' => [
						'vendors' => $vendor,
						'user'	  => $this->user
				]
			]);
	}

	/**
	 * Store a newly created vendor resource in storage.
	 * @param VendorConfigRequest $request
	 * @return Response
	 */
	public function store(Request $request)
	{ 
		$isThere=false;
		$request->vendor_id = (int)($request->vendor_id);
		$request->password=Crypt::encrypt($request->password);
		$validation=Validator::make($request->all(), [
				'vendor_id' => 'required',
				'email' => 'required|email|max:255|unique:vendor_config,email,NULL,id,vendor_id,'.Input::get('vendor_id').',user_id,'.$this->user->id.'',
				'password' => 'required'
			
		]);
		if($validation->fails())
		{
			$isThere=true;
		}
		$loginBroker=app()->make("LoginBroker",[$request]);
		
		if($request->captcha)
			$orders=$loginBroker->checkLogin($request->captcha,$request->postfields);
		else
			$orders=$loginBroker->checkLogin();
	
		if($orders['status'])
		{
			unset($orders['status']);
			if(!$isThere)
			{
				$config=VendorConfig::create(['email'=>$request->email,
			  						  'password'=>'abc','vendor_id'=>$request->vendor_id,'user_id'=>$this->user->id]);
		
				foreach ($orders as $order){
					$orderDB =Orders::create(['user_id'=>$this->user->id,'config_id'=>$config->id,'order_placed'=>$order]);
				}
			}
			else
			{
				$config=VendorConfig::where(['email'=>$request->email,'vendor_id'=>$request->vendor_id,'user_id'=>$this->user->id])->first();
			}
			return $this->setStatusCode(201)->respond([
				'data'=>$config,
				'message' => 'Vendor successfully created'
			]);
		}
		else
			
			if(isset($orders['messages']))
			{
				return $this->setStatusCode(422)->respondWithError($orders['messages']);		
			
			}
			else
			{
				return $this->setStatusCode(422)->respondWithError('Username/password incorrect');
			}
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		return $this->index();
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		/*$user=$this->user;
		$config=VendorConfig::findOrFail($id);
		$vendor=Vendor::lists('name','id');
		//var_dump($config);
		return View ('vendor_config.edit',compact('config','vendor','user'));*/
	}

	/**
	 * Update the specified vendor resource in storage.
	 * @param Request $request
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id,Request $request)
	{
		
		$request->vendor_id=(int)$request->vendor_id;
		$config=VendorConfig::findOrFail($id);	
		if($config->email !== $request->email)
		{
		
			$validation=Validator::make($request->all(), [
				'vendor_id' => 'required',
				'email' => 'required|email|max:255|unique:vendor_config,email,NULL,id,vendor_id,'.Input::get('vendor_id').',user_id,'.$this->user->id.'',
				'password' => 'required'
					
			]);
			if($validation->fails())
			{
				return  $this->setStatusCode(409)->respondWithError('User already exists.');
			}
		}
		$request->password=Crypt::encrypt($request->password);
		$loginBroker=app()->make("LoginBroker",[$request]);
		$verified=$loginBroker->checkLogin();
		if($verified['status'])
		{
			unset($verified['status']);
			$orders=Orders::where(['config_id'=>$config->id])->delete();
			$config->update(['email'=>$request->email,
					'password'=>'abc','vendor_id'=>$request->vendor_id]);
			foreach ($verified as $order){
				$orderDB =Orders::create(['user_id'=>$this->user->id,'config_id'=>$config->id,'order_placed'=>$order]);
			}
			return $this->setStatusCode(201)->respond([
					'message' => 'Vendor successfully updated'
			]);
		}
		else
		{
			if(isset($verified['message']))
			{
				return $this->setStatusCode(422)->respondWithError($verified['message']);		
			}
			else
			{
				return $this->setStatusCode(422)->respondWithError('Username/password incorrect');
			}
		}  
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		
		$config=VendorConfig::findOrFail($id);
		$config->delete();
			return $this->setStatusCode(200)->respond([
					'message' => 'Vendor successfully deleted'
			]);
		
	}

}
