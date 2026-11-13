<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Toggles
	|--------------------------------------------------------------------------
	| enabled: record page views via the LogStatistics middleware
	| log_errors: record exceptions passed to Statistic::error()
	| log_input: store the (filtered) request input as JSON
	*/

	'enabled' => env('STATISTICS_ENABLED', true),
	'log_errors' => env('STATISTICS_LOG_ERRORS', true),
	'log_input' => env('STATISTICS_LOG_INPUT', true),

	/*
	|--------------------------------------------------------------------------
	| Storage
	|--------------------------------------------------------------------------
	| connection: database connection for the statistics table (null = default)
	| queue: false to write synchronously after the response is sent,
	|        true for the default queue, or a queue name string
	| retention_days: rows older than this are removed by `statistics:prune`
	*/

	'connection' => env('STATISTICS_DB_CONNECTION'),
	'queue' => env('STATISTICS_QUEUE', false),
	'retention_days' => env('STATISTICS_RETENTION_DAYS', 180),

	/*
	|--------------------------------------------------------------------------
	| User Settings
	|--------------------------------------------------------------------------
	| Attribute names on your User model (dot notation allowed).
	| The userid column is VARCHAR(50), so an email can be stored.
	*/

	'user_id' => null,
	'first_name' => null,
	'last_name' => null,

	/*
	|--------------------------------------------------------------------------
	| Filtering
	|--------------------------------------------------------------------------
	| protected_fields: input keys redacted at any depth (case-insensitive)
	| ignored_urls: request URIs not logged; supports `*` wildcards, e.g. 'api/*'
	| ignored_route_names: route names not logged
	| error_status_code: http_code stored for exceptions without their own status
	*/

	'protected_fields' => [
		'_token',
		'password',
		'password_confirmation',
		'current_password',
		'new_password',
		'token',
		'access_token',
		'refresh_token',
		'api_key',
		'secret',
		'client_secret',
		'credit_card',
		'card_number',
		'cvv',
		'cvc',
		'ssn',
	],
	'ignored_urls' => [],
	'ignored_route_names' => ['users.show'],
	'error_status_code' => 500,

	/*
	|--------------------------------------------------------------------------
	| Dashboard
	|--------------------------------------------------------------------------
	| Admin view of the statistics table, served at /{path}.
	| Access is controlled by the `viewStatistics` gate. The default gate allows:
	|   - everyone in the local environment
	|   - signed-in users whose email is listed in `emails`
	|   - signed-in users with any role listed in `roles`
	|     (uses $user->hasRole() if available, e.g. spatie/laravel-permission,
	|      otherwise compares the `role_attribute` column on the user)
	|
	|   STATISTICS_DASHBOARD_EMAILS=ben@example.com,ops@example.com
	|   STATISTICS_DASHBOARD_ROLES=admin,developer
	|
	| Defining the gate in your app replaces all of the above:
	|
	|   Gate::define('viewStatistics', fn ($user) => $user->is_admin);
	|
	| Requests to the dashboard itself are never logged.
	*/

	'dashboard' => [
		'enabled' => env('STATISTICS_DASHBOARD_ENABLED', true),
		'path' => env('STATISTICS_DASHBOARD_PATH', 'statistics'),
		'domain' => env('STATISTICS_DASHBOARD_DOMAIN'),
		'middleware' => ['web'],
		'emails' => array_filter(array_map('trim', explode(',', (string) env('STATISTICS_DASHBOARD_EMAILS', '')))),
		'roles' => array_filter(array_map('trim', explode(',', (string) env('STATISTICS_DASHBOARD_ROLES', '')))),
		'email_attribute' => 'email',
		'role_attribute' => 'role',
	],

];
