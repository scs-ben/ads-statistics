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

## Dashboard

An admin view of the statistics table, at `/statistics` by default:

- **Overview:** page views, signed-in users, unique IPs, exception count and rate, 4xx/5xx totals, a traffic chart, top exceptions (grouped by file and line), status codes, top pages and most active users
- **Requests:** a paginated log you can filter by type (page views or exceptions), status (`404` or `5xx`), user, or URL/route/file text
- **Detail:** one request or exception with its input (pretty-printed) and full stack trace, plus how many times that exception has happened

Pick the period at the top (hour, 24 hours, 7 days, 30 days) and tick **Auto-refresh** to reload every 30 seconds. Views of the dashboard itself are never logged.

### Who can see it

The local environment can always open it. Elsewhere, list users by email or role in `.env`:

```
STATISTICS_DASHBOARD_EMAILS=ben@example.com,ops@example.com
STATISTICS_DASHBOARD_ROLES=admin,developer
```

Emails are matched case-insensitively against the user's `email` attribute. Roles use `$user->hasAnyRole()` / `hasRole()` when available (e.g. spatie/laravel-permission), otherwise the user's `role` attribute, which can be a string or an array. Change the attribute names with `dashboard.email_attribute` and `dashboard.role_attribute`.

For anything else, define the gate yourself (in `AppServiceProvider::boot()`). It replaces the default rules:

```php
Gate::define('viewStatistics', fn ($user) => $user->is_admin);
```

### Other settings

| `.env` | Default | Purpose |
| --- | --- | --- |
| `STATISTICS_DASHBOARD_ENABLED` | `true` | Set `false` to remove the routes entirely |
| `STATISTICS_DASHBOARD_PATH` | `statistics` | URL path |
| `STATISTICS_DASHBOARD_DOMAIN` | none | Serve it on a specific domain only |

Change `dashboard.middleware` in the config (default `['web']`) to add e.g. `auth`. To customise the pages, run `php artisan vendor:publish --tag=statistics-views`.

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
