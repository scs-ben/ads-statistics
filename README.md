ads-statistics
==============

Agility Data Systems Statistics Tracking Module for Laravel

Step 1:
set up composer:
"ads/statistics": "dev-master"

Step 2:
run migration: 
php artisan migrate --package=ads/statistics

Step 3:
Add alias and service provider to app/config/app.php

...
'Ads\Statistics\StatisticsServiceProvider',
...

and

...
'Statistic'       => 'Ads\Statistics\Statistic',
...
