<?php

namespace Ads\Statistics\Jobs;

use Ads\Statistics\Statistic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class StoreStatistic implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable;

	public $tries = 1;

	public $attributes;

	public function __construct(array $attributes)
	{
		$this->attributes = $attributes;
	}

	public function handle()
	{
		try {
			Statistic::create($this->attributes);
		} catch (Throwable $e) {
			Statistic::logFailure('Queued statistics save failed', $e);
		}
	}
}
