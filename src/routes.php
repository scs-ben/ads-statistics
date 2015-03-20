<?php

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

Route::matched(function($route, $request)
{
	$statistic = new Statistic;
	$statistic->logStatistics($route, $request, Session::get('error_statistic_id'));
});