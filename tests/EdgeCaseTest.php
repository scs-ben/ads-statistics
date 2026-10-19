<?php

namespace Ads\Statistics\Tests;

use Ads\Statistics\Http\Middleware\LogStatistics;
use Ads\Statistics\Jobs\StoreStatistic;
use Ads\Statistics\Recorder;
use Ads\Statistics\Statistic;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Bus;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EdgeCaseTest extends TestCase
{
	protected function defineRoutes($router)
	{
		$router->middleware(LogStatistics::class)->group(function ($router) {
			$router->any('/page', function () {
				return 'ok';
			})->name('page');

			$router->get('/exact', function () {
				return 'exact';
			});
		});

		$router->get('/boom', function () {
			Statistic::error(new RuntimeException('Inside request'));

			return 'handled';
		})->name('boom');
	}

	public function test_user_attributes_are_recorded_from_config()
	{
		config([
			'statistics.user_id' => 'email',
			'statistics.first_name' => 'profile.first',
			'statistics.last_name' => null,
		]);

		$this->actingAs(new GenericUser([
			'id' => 1,
			'email' => str_repeat('a', 60) . '@example.com',
			'profile' => ['first' => 'Ben'],
		]));

		$this->get('/page')->assertOk();

		$stat = Statistic::sole();
		$this->assertSame(50, mb_strlen($stat->userid));
		$this->assertSame('Ben', $stat->firstname);
		$this->assertNull($stat->lastname);
	}

	public function test_exact_ignored_url_still_matches()
	{
		config(['statistics.ignored_urls' => ['/exact']]);

		$this->get('/exact')->assertOk();

		$this->assertSame(0, Statistic::count());
	}

	public function test_input_logging_can_be_disabled()
	{
		config(['statistics.log_input' => false]);

		$this->post('/page', ['foo' => 'bar'])->assertOk();

		$this->assertNull(Statistic::sole()->input);
	}

	public function test_empty_input_is_stored_as_null()
	{
		$this->get('/page')->assertOk();

		$this->assertNull(Statistic::sole()->input);
	}

	public function test_deeply_nested_input_is_capped()
	{
		$nested = 'leaf';
		for ($i = 0; $i < 15; $i++) {
			$nested = ['level' => $nested];
		}

		$this->post('/page', ['data' => $nested])->assertOk();

		$input = Statistic::sole()->input;
		$this->assertStringContainsString('[max depth]', $input);
		$this->assertStringNotContainsString('leaf', $input);
	}

	public function test_error_inside_request_records_request_details()
	{
		$this->get('/boom?x=1')->assertOk();

		$stat = Statistic::sole();
		$this->assertSame('boom', $stat->destination_name);
		$this->assertSame('{"x":"1"}', $stat->input);
		$this->assertStringContainsString('Inside request', $stat->errorMessage);
	}

	public function test_error_outside_request_skips_request_details()
	{
		$stat = Statistic::error(new RuntimeException('Console'));

		$this->assertNull($stat->fresh()->destination_name);
		$this->assertNull($stat->fresh()->ip_address);
	}

	public function test_error_uses_exception_status_code()
	{
		$stat = Statistic::error(new HttpException(403, 'Nope'));

		$this->assertSame('403', (string) $stat->fresh()->http_code);
	}

	public function test_error_status_code_is_configurable()
	{
		config(['statistics.error_status_code' => 599]);

		$this->assertSame('599', (string) Statistic::error(new RuntimeException('x'))->fresh()->http_code);
	}

	public function test_error_logging_can_be_disabled()
	{
		config(['statistics.log_errors' => false]);

		$this->assertNull(Statistic::error(new RuntimeException('x')));
		$this->assertSame(0, Statistic::count());
	}

	public function test_http_and_fatal_error_aliases()
	{
		$request = $this->app['request'];

		$this->assertInstanceOf(Statistic::class, Statistic::httpError($request, new RuntimeException('a')));
		$this->assertInstanceOf(Statistic::class, Statistic::fatalError($request, new RuntimeException('b')));
		$this->assertSame(2, Statistic::count());
	}

	public function test_failures_are_reported_to_the_log()
	{
		$this->breakDatabase();

		$log = new class extends AbstractLogger {
			public $messages = [];

			public function log($level, $message, array $context = []): void
			{
				$this->messages[] = $message;
			}
		};
		$this->app->instance('log', $log);

		Statistic::error(new RuntimeException('x'));

		$this->assertCount(1, $log->messages);
		$this->assertStringStartsWith('Statistics error logging failed', $log->messages[0]);
	}

	public function test_broken_logger_is_also_swallowed()
	{
		$this->breakDatabase();
		$this->app->instance('log', new class extends AbstractLogger {
			public function log($level, $message, array $context = []): void
			{
				throw new RuntimeException('Log disk full');
			}
		});

		$this->assertNull(Statistic::error(new RuntimeException('x')));
	}

	public function test_named_queue_is_used()
	{
		Bus::fake();
		config(['statistics.queue' => 'stats']);

		$this->get('/page')->assertOk();

		Bus::assertDispatched(StoreStatistic::class, function ($job) {
			return $job->queue === 'stats' && $job->attributes['destination_name'] === 'page';
		});
	}

	public function test_job_stores_statistic()
	{
		(new StoreStatistic(['target_url' => 'queued']))->handle();

		$this->assertSame('queued', Statistic::sole()->target_url);
	}

	public function test_job_never_throws_when_database_fails()
	{
		$this->breakDatabase();

		(new StoreStatistic(['target_url' => 'queued']))->handle();

		$this->addToAssertionCount(1);
	}

	public function test_prune_rejects_invalid_retention()
	{
		config(['statistics.retention_days' => 0]);

		$this->artisan('statistics:prune')->assertExitCode(1);
	}

	public function test_custom_connection_is_used()
	{
		config(['statistics.connection' => 'custom']);

		$this->assertSame('custom', (new Statistic)->getConnectionName());
	}

	public function test_clean_string_handles_non_strings()
	{
		$stringable = new class {
			public function __toString()
			{
				return 'stringable';
			}
		};

		$this->assertNull(Statistic::toCleanString(['array']));
		$this->assertNull(Statistic::toCleanString(new \stdClass));
		$this->assertNull(Statistic::toCleanString(''));
		$this->assertSame('stringable', Statistic::toCleanString($stringable));
		$this->assertSame('42', Statistic::toCleanString(42));
	}

	public function test_strip_protected_leaves_scalars_alone()
	{
		$this->assertSame('value', Recorder::stripProtected('value', ['password']));
	}

	public function test_config_and_migrations_are_publishable()
	{
		$this->artisan('vendor:publish', ['--tag' => 'statistics-config', '--force' => true])->assertExitCode(0);

		$this->assertFileExists(config_path('statistics.php'));
		@unlink(config_path('statistics.php'));
	}
}
