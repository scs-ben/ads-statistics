<?php

namespace Ads\Statistics\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuthorizeDashboard
{
	public function handle(Request $request, Closure $next)
	{
		// forUser() lets the gate run for guests too, so it can allow by environment or IP
		if (! Gate::forUser($request->user())->allows('viewStatistics')) {
			abort(403);
		}

		return $next($request);
	}
}
