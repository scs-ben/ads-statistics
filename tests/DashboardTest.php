<?php

namespace Ads\Statistics\Tests;

use Ads\Statistics\Dashboard\Metrics;
use Ads\Statistics\Http\Middleware\LogStatistics;
use Ads\Statistics\Statistic;
use Carbon\Carbon;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\Facades\Gate;

class DashboardTest extends TestCase
{
	protected function defineEnvironment($app)
	{
		parent::defineEnvironment($app);

		$app['config']->set('statistics.dashboard.emails', ['admin@example.com']);
		$app['config']->set('statistics.dashboard.roles', ['admin']);
		$app['config']->set('statistics.user_id', 'email');
	}

	protected function defineRoutes($router)
	{
		$router->get('/app-page', function () {
			return 'app';
		})->middleware('web');
	}

	protected function admin()
	{
		return $this->actingAs(new GenericUser(['id' => 1, 'email' => 'Admin@Example.com']));
	}

	protected function record(array $attributes, $minutesAgo = 5)
	{
		$statistic = new Statistic($attributes);
		$statistic->created_at = Carbon::now()->subMinutes($minutesAgo);
		$statistic->save();

		return $statistic;
	}

	protected function seedTraffic()
	{
		$this->record(['http_code' => 200, 'method' => 'GET', 'destination_url' => '/projects', 'destination_name' => 'projects.index', 'userid' => 'ben@example.com', 'firstname' => 'Ben', 'lastname' => 'Reese', 'ip_address' => '10.0.0.1']);
		$this->record(['http_code' => 200, 'method' => 'GET', 'destination_url' => '/projects', 'destination_name' => 'projects.index', 'userid' => 'ben@example.com', 'ip_address' => '10.0.0.1']);
		$this->record(['http_code' => 404, 'method' => 'GET', 'destination_url' => '/missing_page', 'target_url' => 'missing_page', 'ip_address' => '10.0.0.2']);
		$this->record(['http_code' => 500, 'errorFile' => '/app/Http/Controllers/ProjectController.php', 'errorLine' => 42, 'errorMessage' => "Undefined variable \$project\nTRACE\n#0 ..."]);
		$this->record(['http_code' => 500, 'errorFile' => '/app/Http/Controllers/ProjectController.php', 'errorLine' => 42, 'errorMessage' => "Undefined variable \$project\nTRACE\n#1 ..."]);
		$this->record(['http_code' => 200, 'destination_url' => '/old'], 60 * 24 * 40);
	}

	// --- Access -------------------------------------------------------------

	public function test_guests_are_forbidden_outside_local()
	{
		$this->get('/statistics')->assertForbidden();
	}

	public function test_listed_email_is_allowed_case_insensitively()
	{
		$this->admin()->get('/statistics')->assertOk();
	}

	public function test_unlisted_user_is_forbidden()
	{
		$this->actingAs(new GenericUser(['id' => 2, 'email' => 'someone@example.com']))
			->get('/statistics')
			->assertForbidden();
	}

	public function test_role_column_is_allowed()
	{
		$this->actingAs(new GenericUser(['id' => 3, 'email' => 'x@example.com', 'role' => 'Admin']))
			->get('/statistics')
			->assertOk();
	}

	public function test_role_array_is_allowed()
	{
		$this->actingAs(new GenericUser(['id' => 3, 'email' => 'x@example.com', 'role' => ['editor', 'admin']]))
			->get('/statistics')
			->assertOk();
	}

	public function test_has_role_method_is_used()
	{
		$user = new class(['id' => 4, 'email' => 'x@example.com', 'role' => 'admin']) extends GenericUser {
			public function hasRole($role)
			{
				return $role === 'superuser';
			}
		};

		// The method wins over the column, and 'admin' isn't a role it grants
		$this->actingAs($user)->get('/statistics')->assertForbidden();

		config(['statistics.dashboard.roles' => ['superuser']]);
		$this->actingAs($user)->get('/statistics')->assertOk();
	}

	public function test_has_any_role_method_is_preferred()
	{
		$user = new class(['id' => 5, 'email' => 'x@example.com']) extends GenericUser {
			public function hasAnyRole($roles)
			{
				return in_array('admin', $roles, true);
			}
		};

		$this->actingAs($user)->get('/statistics')->assertOk();
	}

	public function test_empty_lists_allow_nobody()
	{
		config(['statistics.dashboard.emails' => [], 'statistics.dashboard.roles' => []]);

		$this->admin()->get('/statistics')->assertForbidden();
	}

	public function test_local_environment_still_denies_guests()
	{
		$this->app['env'] = 'local';

		$this->get('/statistics')->assertForbidden();
	}

	public function test_local_environment_allows_any_signed_in_user()
	{
		$this->app['env'] = 'local';

		$this->actingAs(new GenericUser(['id' => 2, 'email' => 'someone@example.com']))
			->get('/statistics')
			->assertOk();
	}

	public function test_local_rule_can_be_disabled()
	{
		$this->app['env'] = 'local';
		config(['statistics.dashboard.allow_local' => false]);

		$this->actingAs(new GenericUser(['id' => 2, 'email' => 'someone@example.com']))
			->get('/statistics')
			->assertForbidden();
	}

	public function test_app_defined_gate_replaces_default()
	{
		Gate::define('viewStatistics', function ($user = null) {
			return $user && $user->id === 99;
		});

		$this->admin()->get('/statistics')->assertForbidden();
		$this->actingAs(new GenericUser(['id' => 99]))->get('/statistics')->assertOk();
	}

	// --- Overview -----------------------------------------------------------

	public function test_overview_shows_metrics()
	{
		$this->seedTraffic();

		$this->admin()->get('/statistics')
			->assertOk()
			->assertSee('Page views')
			->assertSee('projects.index')
			->assertSee('Undefined variable $project')
			->assertSee('ProjectController.php:42')
			->assertSee('Ben Reese')
			->assertDontSee('/old');
	}

	public function test_overview_handles_empty_table()
	{
		$this->admin()->get('/statistics')
			->assertOk()
			->assertSee('No traffic in this period.')
			->assertSee('No exceptions in this period.');
	}

	public function test_every_period_renders()
	{
		$this->seedTraffic();

		foreach (array_keys(Metrics::PERIODS) as $period) {
			$this->admin()->get('/statistics?period=' . $period)->assertOk();
		}

		$this->admin()->get('/statistics?period=bogus')->assertOk()->assertSee('Last 24 hours');
	}

	public function test_dashboard_views_are_not_logged()
	{
		// Through the kernel, which re-syncs its groups onto the router on every request
		$this->app->make(HttpKernel::class)->appendMiddlewareToGroup('web', LogStatistics::class);

		$this->admin()->get('/app-page')->assertOk();
		$this->assertSame(1, Statistic::count());

		$this->admin()->get('/statistics')->assertOk();
		$this->admin()->get('/statistics/requests')->assertOk();

		$this->assertSame(1, Statistic::count());
	}

	public function test_bucket_sql_for_each_driver()
	{
		$metrics = new class extends Metrics {
			public function expression($size, $driver)
			{
				return $this->bucketExpression($size, $driver);
			}
		};

		$this->assertSame("strftime('%Y-%m-%d %H:00', \"created_at\")", $metrics->expression('hour', 'sqlite'));
		$this->assertStringStartsWith("date_format(", $metrics->expression('minute', 'mysql'));
		$this->assertStringContainsString("'%Y-%m-%d %H:%i'", $metrics->expression('minute', 'mariadb'));
		$this->assertStringContainsString("'YYYY-MM-DD'", $metrics->expression('day', 'pgsql'));
		$this->assertStringStartsWith('to_char(', $metrics->expression('day', 'pgsql'));
		$this->assertStringContainsString("'yyyy-MM-dd HH:mm'", $metrics->expression('minute', 'sqlsrv'));
		$this->assertStringStartsWith('format(', $metrics->expression('minute', 'sqlsrv'));
	}

	public function test_output_is_escaped()
	{
		$this->record(['http_code' => 500, 'errorFile' => 'x.php', 'errorLine' => 1, 'errorMessage' => '<script>alert(1)</script>']);

		$this->admin()->get('/statistics')
			->assertOk()
			->assertDontSee('<script>alert(1)</script>', false)
			->assertSee('&lt;script&gt;', false);
	}

	// --- Metrics ------------------------------------------------------------

	public function test_summary_counts()
	{
		$this->seedTraffic();

		$summary = (new Metrics('24h'))->summary();

		$this->assertSame(3, $summary['views']);
		$this->assertSame(2, $summary['errors']);
		$this->assertSame(1, $summary['users']);
		$this->assertSame(2, $summary['ips']);
		$this->assertSame(1, $summary['client_errors']);
		$this->assertSame(2, $summary['server_errors']);
		$this->assertEquals(66.67, $summary['error_rate']);
	}

	public function test_summary_with_no_views_has_zero_rate()
	{
		$this->assertEquals(0, (new Metrics)->summary()['error_rate']);
	}

	public function test_series_fills_every_bucket()
	{
		$now = Carbon::parse('2026-11-13 12:30:00');
		Carbon::setTestNow($now);

		$this->record(['http_code' => 200], 10);
		$this->record(['http_code' => 200], 10);
		$this->record(['http_code' => 500, 'errorMessage' => 'x'], 10);

		$series = (new Metrics('1h', $now))->series();

		$this->assertCount(61, $series);
		$this->assertSame(0, $series[0]['views']);

		$bucket = collect($series)->firstWhere('label', '2026-11-13 12:20');
		$this->assertSame(2, $bucket['views']);
		$this->assertSame(1, $bucket['errors']);

		$this->assertCount(31, (new Metrics('30d', $now))->series());
		$this->assertCount(25, (new Metrics('24h', $now))->series());

		Carbon::setTestNow();
	}

	public function test_top_errors_group_by_location()
	{
		$this->seedTraffic();

		$errors = (new Metrics)->topErrors();

		$this->assertCount(1, $errors);
		$this->assertSame(2, (int) $errors[0]->total);
		$this->assertSame('Undefined variable $project', $errors[0]->message);
	}

	public function test_first_line_trims_long_messages()
	{
		$this->assertSame('', Metrics::firstLine(null));
		$this->assertSame(10, mb_strlen(Metrics::firstLine(str_repeat('é', 50), 10)));
	}

	// --- Requests log -------------------------------------------------------

	public function test_requests_filters()
	{
		$this->seedTraffic();

		$this->admin()->get('/statistics/requests')->assertOk()->assertSee('5 matching rows');
		$this->admin()->get('/statistics/requests?type=errors')->assertSee('2 matching rows')->assertSee('Exceptions');
		$this->admin()->get('/statistics/requests?type=views')->assertSee('3 matching rows');
		$this->admin()->get('/statistics/requests?status=4xx')->assertSee('1 matching rows');
		$this->admin()->get('/statistics/requests?status=200')->assertSee('2 matching rows');
		$this->admin()->get('/statistics/requests?user=ben@example.com')->assertSee('2 matching rows');
		$this->admin()->get('/statistics/requests?q=ProjectController')->assertSee('2 matching rows');
		$this->admin()->get('/statistics/requests?period=30d&q=old')->assertSee('0 matching rows');
		$this->admin()->get('/statistics/requests?type=nonsense')->assertSee('5 matching rows');
	}

	public function test_search_wildcards_are_literal()
	{
		$this->seedTraffic();

		// "_" would match any single character if it weren't escaped
		$this->admin()->get('/statistics/requests?q=missing_page')->assertSee('1 matching rows');
		$this->admin()->get('/statistics/requests?q=%25')->assertSee('0 matching rows');
	}

	public function test_requests_empty_state()
	{
		$this->admin()->get('/statistics/requests?q=nothing')->assertOk()->assertSee('Nothing matches these filters.');
	}

	public function test_requests_paginate()
	{
		for ($i = 0; $i < 55; $i++) {
			$this->record(['http_code' => 200, 'destination_url' => '/p' . $i]);
		}

		$this->admin()->get('/statistics/requests')->assertSee('Page 1 of 2')->assertSee('Older');
		$this->admin()->get('/statistics/requests?page=2')->assertSee('Page 2 of 2')->assertSee('Newer');
	}

	// --- Detail -------------------------------------------------------------

	public function test_error_detail_shows_trace_and_occurrences()
	{
		$this->seedTraffic();
		$error = Statistic::whereNotNull('errorMessage')->first();

		$this->admin()->get('/statistics/errors/' . $error->id)
			->assertOk()
			->assertSee('Undefined variable $project')
			->assertSee('Message &amp; stack trace', false)
			->assertSee('2 occurrences')
			->assertSee('No input recorded.');
	}

	public function test_request_detail_pretty_prints_input()
	{
		$view = $this->record(['http_code' => 200, 'method' => 'POST', 'destination_url' => '/form', 'userid' => 'ben@example.com', 'input' => '{"name":"Ben","tags":["a"]}']);

		$this->admin()->get('/statistics/errors/' . $view->id)
			->assertOk()
			->assertSee('POST /form')
			->assertSee('&quot;name&quot;: &quot;Ben&quot;', false)
			->assertDontSee('Message &amp; stack trace', false);
	}

	public function test_non_json_input_is_shown_raw()
	{
		$view = $this->record(['http_code' => 200, 'input' => 'not json']);

		$this->admin()->get('/statistics/errors/' . $view->id)->assertOk()->assertSee('not json');
	}

	public function test_missing_detail_is_404()
	{
		$this->admin()->get('/statistics/errors/999')->assertNotFound();
	}
}
