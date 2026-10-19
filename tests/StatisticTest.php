<?php

namespace Ads\Statistics\Tests;

use Ads\Statistics\Http\Middleware\LogStatistics;
use Ads\Statistics\Jobs\StoreStatistic;
use Ads\Statistics\Statistic;
use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use RuntimeException;

class StatisticTest extends TestCase
{
	protected function defineRoutes($router)
	{
		$router->middleware(LogStatistics::class)->group(function ($router) {
			$router->any('/page', function () {
				return 'ok';
			})->name('page');

			$router->get('/secret', function () {
				return 'hidden';
			})->name('users.show');

			$router->get('/api/thing', function () {
				return 'api';
			});

			$router->get('/missing', function () {
				abort(404);
			});
		});

		// Legacy registration: the model itself as middleware
		$router->get('/legacy', function () {
			return 'legacy';
		})->middleware(Statistic::class);
	}

	public function test_page_view_is_recorded()
	{
		$this->get('/page?foo=bar')->assertOk();

		$stat = Statistic::sole();
		$this->assertSame('200', (string) $stat->http_code);
		$this->assertSame('page', $stat->destination_name);
		$this->assertSame('GET', $stat->method);
		$this->assertSame('{"foo":"bar"}', $stat->input);
	}

	public function test_status_code_comes_from_response()
	{
		$this->get('/missing')->assertNotFound();

		$this->assertSame('404', (string) Statistic::sole()->http_code);
	}

	public function test_legacy_model_middleware_still_works()
	{
		$this->get('/legacy')->assertOk();

		$this->assertSame(1, Statistic::count());
	}

	public function test_protected_fields_are_redacted_at_any_depth()
	{
		$this->post('/page', [
			'name' => 'Ben',
			'Password' => 'top',
			'user' => ['password' => 'nested', 'card_number' => '4111', 'email' => 'a@b.c'],
		])->assertOk();

		$input = json_decode(Statistic::sole()->input, true);

		$this->assertSame('Ben', $input['name']);
		$this->assertSame('[redacted]', $input['Password']);
		$this->assertSame('[redacted]', $input['user']['password']);
		$this->assertSame('[redacted]', $input['user']['card_number']);
		$this->assertSame('a@b.c', $input['user']['email']);
		$this->assertStringNotContainsString('nested', Statistic::sole()->input);
	}

	public function test_ignored_route_names_and_url_wildcards()
	{
		config(['statistics.ignored_urls' => ['api/*']]);

		$this->get('/secret')->assertOk();
		$this->get('/api/thing')->assertOk();

		$this->assertSame(0, Statistic::count());
	}

	public function test_disabled_logging_records_nothing()
	{
		config(['statistics.enabled' => false]);

		$this->get('/page')->assertOk();

		$this->assertSame(0, Statistic::count());
	}

	public function test_long_and_invalid_utf8_values_are_truncated_safely()
	{
		$stat = Statistic::create([
			'errorFile' => str_repeat('é', 300),
			'destination_name' => "bad\xC3\x28" . str_repeat('x', 100),
			'errorMessage' => str_repeat('ü', 40000),
		]);

		$stat->refresh();

		$this->assertSame(128, mb_strlen($stat->errorFile));
		$this->assertSame(64, mb_strlen($stat->destination_name));
		$this->assertTrue(mb_check_encoding($stat->destination_name, 'UTF-8'));
		$this->assertLessThanOrEqual(Statistic::MAX_TEXT_BYTES, strlen($stat->errorMessage));
		$this->assertTrue(mb_check_encoding($stat->errorMessage, 'UTF-8'));
	}

	public function test_error_records_exception()
	{
		$stat = Statistic::error(new RuntimeException('Boom'));

		$this->assertNotNull($stat);
		$this->assertSame('500', (string) $stat->fresh()->http_code);
		$this->assertStringContainsString('Boom', $stat->fresh()->errorMessage);
	}

	public function test_error_never_throws_when_database_fails()
	{
		$this->breakDatabase();

		$this->assertNull(Statistic::error(new Exception(Str::random(10))));
	}

	public function test_middleware_never_throws_when_database_fails()
	{
		$this->breakDatabase();

		$this->get('/page')->assertOk();
	}

	public function test_queue_mode_dispatches_job()
	{
		Bus::fake();
		config(['statistics.queue' => true]);

		$this->get('/page')->assertOk();

		Bus::assertDispatched(StoreStatistic::class);
		$this->assertSame(0, Statistic::count());
	}

	public function test_prune_command_removes_old_rows()
	{
		Statistic::create(['target_url' => 'old'])->forceFill(['created_at' => now()->subDays(400)])->save();
		Statistic::create(['target_url' => 'new']);

		$this->artisan('statistics:prune', ['--days' => 30])->assertExitCode(0);

		$this->assertSame(['new'], Statistic::pluck('target_url')->all());
	}
}
