<?php namespace App\Http\Requests;

use App\Http\Requests\Request;
use Auth;
use Illuminate\Support\Facades\Input;
use JWTAuth;
use Illuminate\Http\JsonResponse;

class VendorConfigRequest extends Request {

	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize()
	{
		return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$token = JWTAuth::getToken();
		$user = JWTAuth::toUser($token);
		return ['vendor_id' => 'required',
				 'email' => 'required|email|max:255|unique:vendor_config,email,NULL,id,vendor_id,'.Input::get('vendor_id').',user_id,'.$user->id,
				 'password' => 'required'
				];
	}
	
	public function response(array $errors)
	{
		$isThere=false;
		if($errors)
		{
			$isThere=true;
		}
		/*$error= array();
		foreach ($errors['email'] as $message)
		{
			$error['message']=$message;
			//dd($error['message']);
		}
		return new JsonResponse(["error"=>$error], 422);*/
		return $isThere;
	}
}
