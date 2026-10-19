<?php

namespace Ads\Statistics;

use Illuminate\Http\Request;

/**
 * Builds statistic attributes from a request. Holds no state.
 */
class Recorder
{
	const MAX_INPUT_DEPTH = 10;

	public static function shouldIgnore(Request $request)
	{
		$route = $request->route();
		$routeName = is_object($route) ? $route->getName() : null;

		if (! empty($routeName) && in_array($routeName, (array) config('statistics.ignored_route_names', []), true)) {
			return true;
		}

		$uri = $request->server('REQUEST_URI');

		foreach ((array) config('statistics.ignored_urls', []) as $pattern) {
			// Exact match keeps old configs working; `*` wildcards match paths like "api/*"
			if ($uri === $pattern || $request->is(ltrim($pattern, '/'))) {
				return true;
			}
		}

		return false;
	}

	public static function fillFromRequest(Statistic $statistic, Request $request, $user = null, $response = null)
	{
		$statistic->ip_address = $request->ip();
		$statistic->destination_url = $request->server('REQUEST_URI');
		$statistic->referer_url = $request->server('HTTP_REFERER');
		$statistic->target_url = $request->path();
		$statistic->method = $request->method();

		if ($response && method_exists($response, 'getStatusCode')) {
			$statistic->http_code = $response->getStatusCode();
		}

		$route = $request->route();

		if (is_object($route)) {
			$statistic->destination_name = $route->getName();
		}

		if ($user === null) {
			$user = $request->user();
		}

		if (! empty($user)) {
			foreach (['userid' => 'user_id', 'firstname' => 'first_name', 'lastname' => 'last_name'] as $column => $configKey) {
				$attribute = config('statistics.' . $configKey);

				if (! empty($attribute)) {
					$statistic->$column = data_get($user, $attribute);
				}
			}
		}

		if (config('statistics.log_input', true)) {
			$statistic->input = self::encodeInput($request);
		}

		return $statistic;
	}

	public static function encodeInput(Request $request)
	{
		$protected = array_map('strtolower', (array) config('statistics.protected_fields', []));
		$inputs = self::stripProtected($request->except(array_keys($request->allFiles())), $protected);

		if (empty($inputs)) {
			return null;
		}

		$encoded = json_encode($inputs, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);

		// Depth is capped and partial output is allowed, so this only fails in pathological cases
		return $encoded === false ? null : $encoded;
	}

	/**
	 * Recursively remove protected keys (case-insensitive) at any depth.
	 */
	public static function stripProtected($data, array $protected, $depth = 0)
	{
		if (! is_array($data)) {
			return $data;
		}

		if ($depth >= self::MAX_INPUT_DEPTH) {
			return '[max depth]';
		}

		$clean = [];

		foreach ($data as $key => $value) {
			if (in_array(strtolower((string) $key), $protected, true)) {
				$clean[$key] = '[redacted]';
				continue;
			}

			$clean[$key] = self::stripProtected($value, $protected, $depth + 1);
		}

		return $clean;
	}
}
