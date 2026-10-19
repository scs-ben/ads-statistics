<?php

namespace Ads\Statistics;

use Ads\Statistics\Console\PruneStatistics;
use Ads\Statistics\Http\Middleware\LogStatistics;
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

		$this->commands([PruneStatistics::class]);
	}
}
