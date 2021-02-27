<?php

use App\Http\Controllers\PagesController;
{
	Route::group([ 'prefix' => 'api', 'middleware' => 'cors'],
			function()
	{
		Route::post('/contact','PagesController@postContactEmail');
		
		Route::controller('password','Auth\PasswordController');
		Route::group(['middleware' =>['jwt.auth', 'jwt.refresh']], function() {
			// Protected routes
			Route::resource('vendorconfig', 'VendorConfigController');
			Route::controller('account', 'AccountsController');
		});
		
		Route::controller('auth', 'AuthController');
	});
}
?>