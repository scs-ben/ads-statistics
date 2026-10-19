<?php

namespace Ads\Statistics\Tests;

use Ads\Statistics\StatisticsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
	protected function getPackageProviders($app)
	{
		return [StatisticsServiceProvider::class];
	}

	protected function defineEnvironment($app)
	{
		$app['config']->set('database.default', 'testing');
		$app['config']->set('database.connections.testing', [
			'driver' => 'sqlite',
			'database' => ':memory:',
			'prefix' => '',
		]);
	}

	/**
	 * Point the model at an empty database so every save fails.
	 */
	protected function breakDatabase()
	{
		config(['database.connections.broken' => ['driver' => 'sqlite', 'database' => ':memory:']]);
		config(['statistics.connection' => 'broken']);
	}

	protected function defineDatabaseMigrations()
	{
		$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
	}
}
