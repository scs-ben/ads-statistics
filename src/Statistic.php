<?php

namespace Ads\Statistics;

use Ads\Statistics\Http\Middleware\LogStatistics;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Throwable;

class Statistic extends Model
{
	protected $table = 'statistics';

	protected $fillable = [
		'http_code',
		'ip_address',
		'destination_url',
		'target_url',
		'destination_name',
		'referer_url',
		'method',
		'userid',
		'firstname',
		'lastname',
		'errorFile',
		'errorLine',
		'errorMessage',
		'input',
	];

	// Max lengths matching the statistics migrations (characters for VARCHAR)
	const MAX_LENGTHS = [
		'http_code' => 16,
		'ip_address' => 45,
		'destination_url' => 200,
		'target_url' => 200,
		'destination_name' => 64,
		'referer_url' => 200,
		'method' => 16,
		'userid' => 50,
		'firstname' => 50,
		'lastname' => 50,
		'errorFile' => 128,
		'errorLine' => 64,
	];

	// MySQL TEXT holds 65,535 bytes; leave headroom
	const MAX_TEXT_BYTES = 65000;

	const TEXT_FIELDS = ['errorMessage', 'input'];

	protected static function booted()
	{
		// Every save is sanitized, however the model was filled
		static::saving(function (Statistic $statistic) {
			$statistic->sanitize();
		});
	}

	public function getConnectionName()
	{
		return config('statistics.connection') ?: parent::getConnectionName();
	}

	/**
	 * @deprecated Register \Ads\Statistics\Http\Middleware\LogStatistics instead.
	 */
	public function handle(Request $request, Closure $next)
	{
		$middleware = app(LogStatistics::class);
		$response = $middleware->handle($request, $next);
		$middleware->terminate($request, $response);

		return $response;
	}

	/**
	 * Record an exception. Safe to call from the exception handler: it never throws.
	 */
	public static function error(Throwable $e, $user = null, $request = null)
	{
		if (! config('statistics.log_errors', true)) {
			return null;
		}

		try {
			$statistic = new static([
				'http_code' => self::statusCodeFor($e),
				'errorFile' => $e->getFile(),
				'errorLine' => $e->getLine(),
				'errorMessage' => $e->getMessage() . PHP_EOL . 'TRACE' . PHP_EOL . $e->__toString(),
			]);

			$request = $request ?: (app()->bound('request') ? app('request') : null);

			// Console commands get a placeholder request with no route; only record real ones
			if ($request instanceof Request && is_object($request->route())) {
				Recorder::fillFromRequest($statistic, $request, $user);
			}

			$statistic->save();

			return $statistic;
		} catch (Throwable $inner) {
			// Throwing here would mask the original error
			self::logFailure('Statistics error logging failed', $inner);

			return null;
		}
	}

	public static function httpError($request, Throwable $e)
	{
		return self::error($e, null, $request);
	}

	public static function fatalError($request, Throwable $e)
	{
		return self::error($e, null, $request);
	}

	public static function logFailure($message, Throwable $e)
	{
		try {
			logger()->error($message . ': ' . $e->getMessage());
		} catch (Throwable $ignored) {
			// Logger itself unavailable; nothing more we can safely do
		}
	}

	private static function statusCodeFor(Throwable $e)
	{
		if (method_exists($e, 'getStatusCode')) {
			return $e->getStatusCode();
		}

		return config('statistics.error_status_code', 500);
	}

	/**
	 * Coerce every attribute to valid UTF-8 and truncate it to fit its column.
	 */
	public function sanitize()
	{
		foreach (self::MAX_LENGTHS as $field => $max) {
			if (array_key_exists($field, $this->attributes)) {
				$this->attributes[$field] = self::truncateChars($this->attributes[$field], $max);
			}
		}

		foreach (self::TEXT_FIELDS as $field) {
			if (array_key_exists($field, $this->attributes)) {
				$this->attributes[$field] = self::truncateBytes($this->attributes[$field], self::MAX_TEXT_BYTES);
			}
		}

		return $this;
	}

	public static function toCleanString($value)
	{
		if ($value === null || $value === '') {
			return null;
		}

		if (! is_scalar($value) && ! (is_object($value) && method_exists($value, '__toString'))) {
			return null;
		}

		return mb_scrub((string) $value, 'UTF-8');
	}

	public static function truncateChars($value, $max)
	{
		$value = self::toCleanString($value);

		return $value === null ? null : mb_substr($value, 0, $max, 'UTF-8');
	}

	public static function truncateBytes($value, $maxBytes)
	{
		$value = self::toCleanString($value);

		return $value === null ? null : mb_strcut($value, 0, $maxBytes, 'UTF-8');
	}
}
