<?php

namespace Ads\Statistics\Tests;

class DashboardDisabledTest extends TestCase
{
	protected function defineEnvironment($app)
	{
		parent::defineEnvironment($app);

		$app['config']->set('statistics.dashboard.enabled', false);
		$app['env'] = 'local';
	}

	public function test_routes_are_not_registered()
	{
		$this->get('/statistics')->assertNotFound();
	}
}
