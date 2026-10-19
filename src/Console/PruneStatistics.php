<?php

namespace Ads\Statistics\Console;

use Ads\Statistics\Statistic;
use Illuminate\Console\Command;

class PruneStatistics extends Command
{
	protected $signature = 'statistics:prune {--days= : Delete rows older than this many days (defaults to config)}';

	protected $description = 'Delete old rows from the statistics table';

	public function handle()
	{
		$days = (int) ($this->option('days') ?: config('statistics.retention_days', 180));

		if ($days < 1) {
			$this->error('Retention must be at least 1 day.');

			return 1;
		}

		$cutoff = now()->subDays($days);
		$total = 0;

		// Delete in chunks so a large table doesn't lock for long
		do {
			$deleted = Statistic::query()
				->where('created_at', '<', $cutoff)
				->limit(1000)
				->delete();

			$total += $deleted;
		} while ($deleted > 0);

		$this->info("Deleted {$total} statistics older than {$days} days.");

		return 0;
	}
}
