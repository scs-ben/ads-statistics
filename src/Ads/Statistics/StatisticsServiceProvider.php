<?php namespace Ads\Statistics;

use Statistic;
use Illuminate\Support\ServiceProvider;

class StatisticsServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	public function boot()
	{
		// Publish the config file
		$this->publishes([
				__DIR__.'/../config/statistics.php' => config_path('statistics.php'),
		]);
		
		// Publish your migrations
		$this->publishes([
				__DIR__.'/../database/migrations/' => base_path('/database/migrations')
		], 'migrations');
		
		include __DIR__.'/../../routes.php';
	}
	
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->mergeConfigFrom(
		    __DIR__.'/../config/statistics.php', 'statistics'
		);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
// 	public function provides()
// 	{
// 		return [];
// 	}

}
