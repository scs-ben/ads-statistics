<?php

namespace Ads\Statistics\Dashboard;

use Ads\Statistics\Statistic;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregate queries behind the dashboard. Every query is bounded by the period.
 */
class Metrics
{
	// key => [label, minutes, bucket size]
	const PERIODS = [
		'1h' => ['Last hour', 60, 'minute'],
		'24h' => ['Last 24 hours', 1440, 'hour'],
		'7d' => ['Last 7 days', 10080, 'hour'],
		'30d' => ['Last 30 days', 43200, 'day'],
	];

	const DEFAULT_PERIOD = '24h';

	public $period;

	public $from;

	public $to;

	public function __construct($period = null, Carbon $now = null)
	{
		$this->period = array_key_exists($period, self::PERIODS) ? $period : self::DEFAULT_PERIOD;
		$this->to = $now ? $now->copy() : Carbon::now();
		$this->from = $this->to->copy()->subMinutes(self::PERIODS[$this->period][1]);
	}

	public function label()
	{
		return self::PERIODS[$this->period][0];
	}

	public function query()
	{
		return Statistic::query()->whereBetween('created_at', [$this->from, $this->to]);
	}

	/**
	 * Page views are rows written by the middleware; errors are rows with an exception.
	 */
	public static function views(Builder $query)
	{
		return $query->whereNull('errorMessage');
	}

	public static function errors(Builder $query)
	{
		return $query->whereNotNull('errorMessage');
	}

	public function summary()
	{
		$views = self::views($this->query())->count();
		$errors = self::errors($this->query())->count();

		return [
			'views' => $views,
			'errors' => $errors,
			'users' => self::views($this->query())->whereNotNull('userid')->distinct()->count('userid'),
			'ips' => self::views($this->query())->whereNotNull('ip_address')->distinct()->count('ip_address'),
			'client_errors' => self::views($this->query())->where('http_code', 'like', '4%')->count(),
			'server_errors' => $this->query()->where('http_code', 'like', '5%')->count(),
			'error_rate' => $views > 0 ? round($errors / $views * 100, 2) : 0.0,
		];
	}

	/**
	 * Views and errors per time bucket, with empty buckets filled in.
	 */
	public function series()
	{
		$size = self::PERIODS[$this->period][2];
		$bucket = $this->bucketExpression($size);
		$message = $this->wrap('errorMessage');

		$rows = $this->query()
			->selectRaw("{$bucket} as bucket")
			->selectRaw("sum(case when {$message} is null then 1 else 0 end) as views")
			->selectRaw("sum(case when {$message} is null then 0 else 1 end) as errors")
			->groupBy(DB::raw($bucket))
			->toBase()
			->get()
			->keyBy('bucket');

		$series = [];
		$cursor = $this->bucketStart($this->from, $size);

		while ($cursor <= $this->to) {
			$key = $cursor->format(self::bucketFormat($size));
			$row = $rows->get($key);

			$series[] = [
				'label' => $key,
				'views' => $row ? (int) $row->views : 0,
				'errors' => $row ? (int) $row->errors : 0,
			];

			$cursor = $cursor->copy()->add(1, $size);
		}

		return $series;
	}

	public function topRoutes($limit = 10)
	{
		return self::views($this->query())
			->selectRaw('coalesce(destination_name, target_url) as route, count(*) as hits')
			->selectRaw("sum(case when http_code like '4%' or http_code like '5%' then 1 else 0 end) as failures")
			->groupBy(DB::raw('coalesce(destination_name, target_url)'))
			->orderByDesc('hits')
			->limit($limit)
			->toBase()
			->get();
	}

	public function topUsers($limit = 10)
	{
		return self::views($this->query())
			->whereNotNull('userid')
			->selectRaw('userid, max(firstname) as firstname, max(lastname) as lastname, count(*) as hits, max(created_at) as last_seen')
			->groupBy('userid')
			->orderByDesc('hits')
			->limit($limit)
			->toBase()
			->get();
	}

	public function statusCodes()
	{
		return $this->query()
			->whereNotNull('http_code')
			->selectRaw('http_code, count(*) as total')
			->groupBy('http_code')
			->orderByDesc('total')
			->toBase()
			->get();
	}

	/**
	 * Exceptions grouped by where they were thrown, with the latest message for each.
	 */
	public function topErrors($limit = 10)
	{
		$groups = self::errors($this->query())
			->select('errorFile', 'errorLine')
			->selectRaw('count(*) as total, max(id) as latest_id, max(created_at) as last_seen')
			->groupBy('errorFile', 'errorLine')
			->orderByDesc('total')
			->limit($limit)
			->toBase()
			->get();

		$messages = Statistic::query()
			->whereIn('id', $groups->pluck('latest_id'))
			->pluck('errorMessage', 'id');

		return $groups->map(function ($group) use ($messages) {
			$group->message = self::firstLine($messages->get($group->latest_id));

			return $group;
		});
	}

	public static function firstLine($message, $max = 160)
	{
		$line = strtok((string) $message, "\r\n");

		return mb_strimwidth($line === false ? '' : $line, 0, $max, '…', 'UTF-8');
	}

	protected function wrap($column)
	{
		return (new Statistic)->getConnection()->getQueryGrammar()->wrap($column);
	}

	protected function bucketExpression($size, $driver = null)
	{
		$column = $this->wrap('created_at');
		$driver = $driver ?: (new Statistic)->getConnection()->getDriverName();

		$formats = [
			'sqlite' => ['minute' => '%Y-%m-%d %H:%M', 'hour' => '%Y-%m-%d %H:00', 'day' => '%Y-%m-%d'],
			'mysql' => ['minute' => '%Y-%m-%d %H:%i', 'hour' => '%Y-%m-%d %H:00', 'day' => '%Y-%m-%d'],
			'pgsql' => ['minute' => 'YYYY-MM-DD HH24:MI', 'hour' => 'YYYY-MM-DD HH24:00', 'day' => 'YYYY-MM-DD'],
			'sqlsrv' => ['minute' => 'yyyy-MM-dd HH:mm', 'hour' => 'yyyy-MM-dd HH:00', 'day' => 'yyyy-MM-dd'],
		];

		switch ($driver) {
			case 'sqlite':
				return "strftime('{$formats['sqlite'][$size]}', {$column})";
			case 'pgsql':
				return "to_char({$column}, '{$formats['pgsql'][$size]}')";
			case 'sqlsrv':
				return "format({$column}, '{$formats['sqlsrv'][$size]}')";
			default:
				// mysql and mariadb
				return "date_format({$column}, '{$formats['mysql'][$size]}')";
		}
	}

	protected function bucketStart(Carbon $time, $size)
	{
		return $time->copy()->startOf($size);
	}

	protected static function bucketFormat($size)
	{
		return ['minute' => 'Y-m-d H:i', 'hour' => 'Y-m-d H:00', 'day' => 'Y-m-d'][$size];
	}
}
