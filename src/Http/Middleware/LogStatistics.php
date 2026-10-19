<?php

namespace Ads\Statistics\Http\Middleware;

use Ads\Statistics\Jobs\StoreStatistic;
use Ads\Statistics\Recorder;
use Ads\Statistics\Statistic;
use Closure;
use Illuminate\Http\Request;
use Throwable;

class LogStatistics
{
	public function handle(Request $request, Closure $next)
	{
		return $next($request);
	}

	/**
	 * Runs after the response has been sent, so logging never delays the user.
	 */
	public function terminate($request, $response)
	{
		try {
			if (! config('statistics.enabled', true) || Recorder::shouldIgnore($request)) {
				return;
			}

			$statistic = Recorder::fillFromRequest(new Statistic, $request, null, $response);

			if (config('statistics.queue')) {
				$statistic->sanitize();
				StoreStatistic::dispatch($statistic->getAttributes())
					->onQueue(config('statistics.queue') === true ? null : config('statistics.queue'));

				return;
			}

			$statistic->save();
		} catch (Throwable $e) {
			// Never let statistics logging break the request
			Statistic::logFailure('Statistics logging failed', $e);
		}
	}
}
