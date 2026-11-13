<?php

use Ads\Statistics\Http\Controllers\DashboardController;
use Ads\Statistics\Http\Middleware\AuthorizeDashboard;
use Illuminate\Support\Facades\Route;

Route::group([
	'domain' => config('statistics.dashboard.domain'),
	'prefix' => trim(config('statistics.dashboard.path', 'statistics'), '/'),
	'middleware' => array_merge((array) config('statistics.dashboard.middleware', ['web']), [AuthorizeDashboard::class]),
	'as' => 'statistics.',
], function () {
	Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
	Route::get('/requests', [DashboardController::class, 'requests'])->name('requests');
	Route::get('/errors/{statistic}', [DashboardController::class, 'show'])->name('show')->where('statistic', '[0-9]+');
});
