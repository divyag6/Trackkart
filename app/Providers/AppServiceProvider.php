<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		//
	}

	/**
	 * Register any application services.
	 *
	 * This service provider is a great spot to register your various container
	 * bindings with the application. As you can see, we are registering our
	 * "Registrar" implementation here. You can add your own bindings too!
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind(
			'Illuminate\Contracts\Auth\Registrar',
			'App\Services\Registrar'
		);
		$this->app->bind('LoginBroker','App\Domain\LoginBroker');
		$this->app->bind('pepperfry','App\Domain\PepperfryResource');
		$this->app->bind('fabfurnish','App\Domain\FabfurnishResource');
		$this->app->bind('flipkart','App\Domain\FlipkartResource');
		$this->app->bind('amazon','App\Domain\AmazonResource');
		$this->app->bind('snapdeal','App\Domain\SnapdealResource');
		$this->app->bind('jabong','App\Domain\JabongResource');
		$this->app->bind('OrderRepository','App\Domain\OrderRepository');
	}

}
