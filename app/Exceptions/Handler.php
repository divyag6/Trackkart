<?php namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException as ModelNotFoundException;
use ErrorException;
use Illuminate\Database\QueryException as QueryException;
use Response;
use App\Http\Controllers\ApiController;


class Handler extends ExceptionHandler {

	/**
	 * A list of the exception types that should not be reported.
	 *
	 * @var array
	 */
	protected $dontReport = [
		'Symfony\Component\HttpKernel\Exception\HttpException'
	];

	/**
	 * Report or log an exception.
	 *
	 * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	public function report(Exception $e)
	{
		return parent::report($e);
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Illuminate\Http\Response
	 */
	public function render($request, Exception $e)
	{
		
		
		$controller=app()->make('App\Http\Controllers\ApiController');
		
		if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException)
		{
			return $controller->setStatusCode(401)->respondWithError('Token Invalid');
			//return response(['Token is invalid'], 401);
		}
		if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException)
		{
			return $controller->setStatusCode(401)->respondWithError('Token has expired');
			//return response(['Token has expired'], 401);
		}
		if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenBlacklistedException)
		{
			return $controller->setStatusCode(400)->respondWithError('Token has been blacklisted');
			//return response(['Token has expired'], 401);
		}
		if ($e instanceof ModelNotFoundException)
		{
			//$controller=app()->make('App\Http\Controllers\ApiController');
			return $controller->respondNotFound('Entity not found');
		
		}
	/*	if ($e instanceof ErrorException)
		{
			
			
		}
		else*/
		
			return parent::render($request, $e);
		
	}

}
