<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateLoginRequest;
use App\User;
use App\Domain;
use Illuminate\Http\Request;
use App\Http\Requests\AccountsRequest;
use Illuminate\Auth\Authenticatable;
use Auth;
use App\Domain\OrderRepository;
use App\VendorConfig;
use App\Domain\PepperfryResource;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use PhpParser\Node\Expr\Print_;
use Crypt;
use App\Commands\GetAllOrders;
use App\Http\Controllers\ApiController;
use JWTAuth;
use App\Orders;
use App\Vendor;
use Illuminate\Support\Collection;

class AccountsController extends ApiController {

	use AuthenticatesAndRegistersUsers;
	protected $user;
	//protected $redirectTo='/auth/login';
	//protected $loginPath='/account/create';
	protected $orders=array(array());
	
	
	public function  __construct()
	{
		$token = JWTAuth::getToken();
		$this->user = JWTAuth::toUser($token);
		//$this->middleware('jwt.auth');
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function postRefreshOrders(Request $request)
	{
		
		$input=json_decode($request->getContent(),true);
		$configs=Collection::make($input);
		//$configs=VendorConfig::where('user_id','=',$this->user->id)->get();
		if(!$configs->isEmpty())
		{
			$repository=app()->make('OrderRepository',[$configs]);
		
			$this->orders=$repository->dispatchCurlJobs();
			if(!isset($this->orders['messages'])&& empty($this->orders[0]))
			{
				return $this->respondNotFound('No Orders Found');
			}
			elseif (isset($this->orders['messages'])&& !empty($this->orders[0]))
			{
				$messages=$this->orders['messages'];
				unset($this->orders['messages']);
				$orders=Orders::where(['user_id'=>$this->user->id])->delete();
				foreach ($this->orders as $order){
					
					$orderDB =Orders::create(['user_id'=>$this->user->id,'config_id'=>$order['configId'],'order_placed'=>$order]);
				}
				return $this->setStatusCode(200)->respond([
						'data' => $this->orders,
						'message'=>$messages
				]);
				
			}
			elseif(isset($this->orders['messages'])&& empty($this->orders[0]))
			{
				return $this->setStatusCode(500)->respondWithError($this->orders['messages']);
			//	return View('account')->with(['orders'=>$this->orders,'user'=>$this->user]);
			}
			else {
				$orders=Orders::where(['user_id'=>$this->user->id])->delete();
				foreach ($this->orders as $order){
					$orderDB =Orders::create(['user_id'=>$this->user->id,'config_id'=>$order['configId'],'order_placed'=>$order]);	
				}
				
				return $this->setStatusCode(200)->respond([
						'data' => $this->orders
				]);
			}
			
		}
		else 
		{
			return $this->respondNotFound('No vendor configurations');
			//return View('account')->with(['message'=>'No vendors found!','subMessage'=> 'Please configure vendors.', 'user'=>$this->user]);
		}
		
	}

	/**
	 * Show the form for changing password.
	 *
	 * @return Response
	 */
	public function create()
	{
		return View('change_password')->with('user',$this->user);
	}

	/**
	 * Store the newly updated password.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
		
		$this->validate($request, [
			'old_password' => 'required',
			'password' => 'required|confirmed',	
		]);
			$password_old=bcrypt($request->old_password);
			
			
			$credentials=['email'=>$request->email, 'password' => $request->old_password];
			
			
			if(Auth::validate($credentials))
			{
				User::where('id','=',Auth::id())->update(['password'=>bcrypt($request->password)]);
				Auth::logout();
				return redirect()->intended($this->redirectPath())->with(['flash_message'=>'Password successfully updated!']);;
			}
			
			return redirect($this->loginPath())
			->withInput($request->only('email'))
			->withErrors([
					'email' => 'Check the old password provided',
					]);
						
	}
	
	public function getOrders()
	{
		$orders=Orders::where('user_id','=',$this->user->id)->get();
		if(!$orders->isEmpty())
		{
			return $this->setStatusCode(200)->respond([
					'data' => $orders
			]);
		}
		else
		{
			return $this->respondNotFound('No orders found');
		}
	}


}
