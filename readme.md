ads-statistics
==============

<h3>Audit/Error Logging Module for Laravel</h3>

The module will track the page view history for all users of the associated website. The module will log 500 error statistics to help with debugging code. Additionaly, the posted data for form submission will be saved in the database. The settings file has an array option that allows you to remove sensitive files from being saved in the tracking table.

Once configired, this plugin will automatically save page viewing history to the database.

_(Make sure to run "php artisan migrate" if updating)_

Step 1:

Set up composer, add the package to your require tag:
```
For Laravel 6,7,8,9+
"ads/statistics": "3.0^"
```

run
```
composer update
```

Step 2:

Publish and run migrations: (You have to add the SP to the config/app.php)
```
php artisan vendor:publish --provider=Ads\Statistics\StatisticsServiceProvider
php artisan migrate
```

Step 3:

Add Statistic logging to 'web' middleware in `app\Http\Kernel.php`:
```
    protected $middlewareGroups = [
        'web' => [
            ...
            \Ads\Statistics\Statistic::class,
        ],
```

Step 4:

In order to log 500 errors, you'll need to add some code to the app/Exceptions/Handler.php Add this interceptor to the register function
Add to or create the *report* function before the return:
```
public function register()
{
	$this->reportable(function (Throwable $e) {
	    //
	});

	$this->reportable(function (\Exception $e) {
	    \Statistic::error($e);
	});
}
```

_* Step 4 is only necessary if you have user authentication_

Step 5:

Edit the _<b>config/statistics.php</b>_ file.

Please enter the column names from your user database table.

For example:
```
  'user_id' => 'email',
  'first_name' => 'first_name',
	'last_name' => 'last_name',
	'protected_fields' => ['password'],
```
