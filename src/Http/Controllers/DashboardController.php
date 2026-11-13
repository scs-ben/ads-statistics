<?php

namespace Ads\Statistics\Http\Controllers;

use Ads\Statistics\Dashboard\Metrics;
use Ads\Statistics\Statistic;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
	public function index(Request $request)
	{
		$metrics = new Metrics($request->query('period'));

		return view('statistics::dashboard', [
			'metrics' => $metrics,
			'summary' => $metrics->summary(),
			'series' => $metrics->series(),
			'topRoutes' => $metrics->topRoutes(),
			'topUsers' => $metrics->topUsers(),
			'topErrors' => $metrics->topErrors(),
			'statusCodes' => $metrics->statusCodes(),
		]);
	}

	public function requests(Request $request)
	{
		$metrics = new Metrics($request->query('period'));
		$filters = [
			'type' => in_array($request->query('type'), ['views', 'errors'], true) ? $request->query('type') : 'all',
			'status' => (string) $request->query('status', ''),
			'user' => (string) $request->query('user', ''),
			'q' => (string) $request->query('q', ''),
		];

		$query = $metrics->query();

		if ($filters['type'] === 'views') {
			Metrics::views($query);
		} elseif ($filters['type'] === 'errors') {
			Metrics::errors($query);
		}

		if ($filters['status'] !== '') {
			// "4xx" style filters match the whole class
			preg_match('/^([1-5])xx$/i', $filters['status'], $class)
				? $query->where('http_code', 'like', $class[1] . '%')
				: $query->where('http_code', $filters['status']);
		}

		if ($filters['user'] !== '') {
			$query->where('userid', $filters['user']);
		}

		if ($filters['q'] !== '') {
			// "!" as the escape character behaves the same on every driver; backslash does not
			$like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']) . '%';
			$grammar = $query->getQuery()->getGrammar();

			$query->where(function ($query) use ($like, $grammar) {
				foreach (['destination_url', 'destination_name', 'errorFile'] as $column) {
					$query->orWhereRaw($grammar->wrap($column) . " like ? escape '!'", [$like]);
				}
			});
		}

		$rows = $query->orderByDesc('created_at')
			->orderByDesc('id')
			->paginate(50, ['id', 'created_at', 'http_code', 'method', 'destination_url', 'destination_name', 'userid', 'firstname', 'lastname', 'ip_address', 'errorFile', 'errorLine', 'errorMessage'])
			->appends($request->query());

		return view('statistics::requests', compact('metrics', 'filters', 'rows'));
	}

	public function show(Statistic $statistic)
	{
		$input = json_decode((string) $statistic->input, true);

		return view('statistics::show', [
			'statistic' => $statistic,
			'input' => is_array($input) ? json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $statistic->input,
			'similar' => $statistic->errorFile
				? Statistic::query()->where('errorFile', $statistic->errorFile)->where('errorLine', $statistic->errorLine)->count()
				: 0,
		]);
	}
}
