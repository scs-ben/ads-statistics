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
'Ads\Statistics\StatisticsServiceProvider',
and
'Statistic'       => 'Ads\Statistics\Statistic',

Step 4:
Update the config/settings file to provide a value that matches your user's id and a first/last/real name field.
