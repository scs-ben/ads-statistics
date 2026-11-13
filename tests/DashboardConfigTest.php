<?php

namespace Ads\Statistics\Tests;

use Illuminate\Auth\GenericUser;

class DashboardConfigTest extends TestCase
{
	protected function defineEnvironment($app)
	{
		parent::defineEnvironment($app);

		$app['config']->set('statistics.dashboard.path', '/admin/stats/');
		$app['config']->set('statistics.dashboard.emails', ['admin@example.com']);
	}

	public function test_custom_path_is_used()
	{
		$this->actingAs(new GenericUser(['id' => 1, 'email' => 'admin@example.com']));

		$this->get('/admin/stats')->assertOk();
		$this->get('/statistics')->assertNotFound();
	}

	public function test_views_are_publishable()
	{
		$this->artisan('vendor:publish', ['--tag' => 'statistics-views', '--force' => true])->assertExitCode(0);

		$this->assertFileExists(resource_path('views/vendor/statistics/dashboard.blade.php'));
		$this->app['files']->deleteDirectory(resource_path('views/vendor/statistics'));
	}
}
