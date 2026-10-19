ads-statistics
==============

### Audit/Error Logging Module for Laravel

Records page views (URL, route, status code, user, filtered form input) and exceptions to a `statistics` table to help with usage reporting and debugging 500 errors.

Logging is designed to never break your app: every value is cleaned to valid UTF-8 and truncated to fit its column, and any failure while logging is written to the Laravel log and swallowed. Page views are written after the response has been sent (or queued), so they don't slow requests down.

Supports Laravel 7–13 on PHP 7.3+.

## Installation

```
composer require ads/statistics
php artisan vendor:publish --provider="Ads\Statistics\StatisticsServiceProvider"
php artisan migrate
```

_Upgrading?_ Publish and run migrations again; `update_statistics_table` widens `ip_address` for IPv6 and adds indexes. On Laravel 7–9 it requires `composer require doctrine/dbal`.

## Log page views

Add the middleware to the `web` group.

Laravel 11+ (`bootstrap/app.php`):
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Ads\Statistics\Http\Middleware\LogStatistics::class,
    ]);
})
```

Laravel 7–10 (`app/Http/Kernel.php`):
```php
protected $middlewareGroups = [
    'web' => [
        // ...
        \Ads\Statistics\Http\Middleware\LogStatistics::class,
    ],
];
```

Registering `\Ads\Statistics\Statistic::class` as middleware still works but is deprecated; it writes synchronously before the response is sent.

## Log exceptions

Laravel 11+ (`bootstrap/app.php`):
```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->report(function (Throwable $e) {
        \Ads\Statistics\Statistic::error($e);
    });
})
```

Laravel 8–10 (`app/Exceptions/Handler.php`):
```php
public function register()
{
    $this->reportable(function (Throwable $e) {
        \Ads\Statistics\Statistic::error($e);
    });
}
```

`Statistic::error()` never throws. Exceptions with their own status (e.g. `HttpException`) record that code; others record `error_status_code` (500).

## Configuration

Edit `config/statistics.php`. The main options:

| Key | Default | Purpose |
| --- | --- | --- |
| `enabled` / `log_errors` / `log_input` | `true` | Toggle page views, exceptions, and input capture |
| `user_id`, `first_name`, `last_name` | `null` | Attribute names on your User model, e.g. `'email'` |
| `protected_fields` | passwords, tokens, card numbers… | Keys redacted at any depth, case-insensitive |
| `ignored_urls` | `[]` | URIs to skip; supports wildcards like `'api/*'` |
| `ignored_route_names` | `['users.show']` | Route names to skip |
| `queue` | `false` | `true` or a queue name to write via a queued job |
| `connection` | `null` | Separate database connection for the table |
| `retention_days` | `180` | Used by `statistics:prune` |

Most can be set via `.env` (`STATISTICS_ENABLED`, `STATISTICS_QUEUE`, `STATISTICS_RETENTION_DAYS`, …).

## Pruning old rows

```
php artisan statistics:prune            # uses retention_days
php artisan statistics:prune --days=30
```

Schedule it daily, e.g. `Schedule::command('statistics:prune')->daily();`.

## Tests

```
composer install
composer test
```
