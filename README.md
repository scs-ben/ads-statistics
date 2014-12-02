ads-statistics
==============

Agility Data Systems Statistics Tracking Module for Laravel

(Make sure to run "php artisan migrate --package=ads/statistics" if updating)

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

* Steps 4,5 are not necessary if you don't have user authentication

Step 4:
Publish the config file so that you can edit it in app/config/packages/ads/statistics/settings.php
php artisan config:publish ads/statistics

Step 5:
Edit the app/config/packages/ads/statistics/settings.php file to provide a value that matches your user's id and first name and/or last name field (the value entered should be the string name of the column).
*Note: if you do not do this step, the username and id will not be stored in the database
