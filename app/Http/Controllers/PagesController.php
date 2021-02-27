<?php namespace  App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Mail;
use App\Http\Controllers\ApiController;

class PagesController extends ApiController{
	
	protected $user;
	
	public function __construct()
	{
		$this->user=Auth::user();
	}
	
	public function home()
	{
	return View('home')->with('user',$this->user);
	}
	public function about()
	{
		return View('about')->with(['user'=>$this->user,'message'=>'Page under Construction.']);
	}
	
	public function contact()
	{
		return View('contact')->with(['user'=>$this->user,'message'=>'Page under Construction.']);
	}
	/**
	 * Send an email to support@trackkart.com
	 *
	 * @param  Request  $request
	 * @return Response
	 */
	public function postContactEmail(Request $request)
	{
		$this->validate($request, ['name'=> 'required',
								   'email' => 'required|email']);
		
		$message ='Message:'.$request->message."\n\n".'from:'.$request->name."\n".$request->email."\n";
				
		
		$status=Mail::raw($message,function($m) use($request)
		{
			
			$m->to('support@trackkart.com')->subject($request->subject);
			
					
		});
		
		if($status==1) return $this->setStatusCode(200)->respond([
					'message' => 'Email successfully sent'
			]);
		else return $this->setStatusCode(422)->respondWithError('Email could not be sent');
	}
}
