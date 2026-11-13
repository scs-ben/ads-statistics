<?php

namespace Ads\Statistics;

use Ads\Statistics\Console\PruneStatistics;
use Ads\Statistics\Dashboard\Access;
use Ads\Statistics\Http\Middleware\LogStatistics;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class StatisticsServiceProvider extends ServiceProvider
{
	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/statistics.php', 'statistics');

		// Singleton so the instance that handled the request is the one terminated
		$this->app->singleton(LogStatistics::class);
	}

	public function boot()
	{
		$this->publishes([
			__DIR__ . '/../config/statistics.php' => config_path('statistics.php'),
		], 'statistics-config');

		$this->publishes([
			__DIR__ . '/../database/migrations/' => database_path('migrations'),
		], 'migrations');

		$this->publishes([
			__DIR__ . '/../resources/views' => resource_path('views/vendor/statistics'),
		], 'statistics-views');

		$this->commands([PruneStatistics::class]);

		$this->loadViewsFrom(__DIR__ . '/../resources/views', 'statistics');

		if (config('statistics.dashboard.enabled', true)) {
			$this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
			$this->defineDefaultGate();
		}
	}

	/**
	 * Local, listed emails and listed roles by default, until the app defines its own gate.
	 */
	protected function defineDefaultGate()
	{
		$this->app->booted(function () {
			if (! Gate::has('viewStatistics')) {
				Gate::define('viewStatistics', function ($user = null) {
					return Access::allows($user);
				});
			}
		});
	}
}
