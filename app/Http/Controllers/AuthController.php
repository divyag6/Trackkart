<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use JWTAuth;
use Illuminate\Contracts\Auth\Registrar;

class AuthController extends ApiController {

	public function  __construct()
	{
		//$this->middleware('cors');
		//$this->middleware('preflight');
	}
	/**
	 * Show the registration form.
	 *
	 * @return Response
	 */
	public function getRegister()
	{
		//
	}

	/**
	 * Register a new user
	 *
	 * @return Response
	 */
	public function postRegister(Request $request,Registrar $registar)
	{
		
		$validation=$registar->validator($request->all());
		
		if ($validation->fails())
		{
			return $this->setStatusCode(409)->respondWithError('User already exists.');
		}

		$user=$registar->create($request->all());
			
	
		$token = JWTAuth::fromUser($user);
		
		return $this->setStatusCode(200)->respond([
					'token' => $token,
					'user' =>$user
			]);
	}

	/**
	 * Show the login form.
	 *
	 * @return Response
	 */
	public function getLogin()
	{
		dd('getLogin');
	}

	/**
	 * Login in a user with given credentials
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function postLogin(Request $request)
	{
		$credentials= $request->only('email','password');
		try {
			// attempt to verify the credentials and create a token for the user
			if (! $token = JWTAuth::attempt($credentials)) {
				return $this->setStatusCode(401)->respondWithError('Invalid credentials');
			}
		} catch (JWTException $e) {
			// something went wrong whilst attempting to encode the token
			return $this->setStatusCode(500)->respondWithError('Could not create token');
		}
		
		$user = JWTAuth::toUser($token);
		// all good so return the token
		return $this->setStatusCode(200)->respond([
					'token' => $token,
					'user' =>$user
			]);

	}

	/**
     * Log out
     * Invalidate the token, so user cannot use it anymore
     * They have to relogin to get a new token
     * 
     * @param Request $request
     */
    public function getLogout(Request $request) {
        $this->validate($request, [
            'token' => 'required' 
        ]);
        
        if(JWTAuth::invalidate($request->input('token'))){
	        return $this->setStatusCode(200)->respond([
	        		'message' => 'Successfully logged out!'
	        ]);
        }
    }


}
