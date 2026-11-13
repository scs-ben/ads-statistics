@extends('statistics::layout')

@section('title', 'Overview')

@section('content')
	<h1>Overview</h1>
	<p class="sub">{{ $metrics->label() }} · {{ $metrics->from->format('M j, H:i') }} – {{ $metrics->to->format('M j, H:i') }}</p>

	<div class="tiles">
		<div class="card tile"><div class="value">{{ number_format($summary['views']) }}</div><div class="label">Page views</div></div>
		<div class="card tile"><div class="value">{{ number_format($summary['users']) }}</div><div class="label">Signed-in users</div></div>
		<div class="card tile"><div class="value">{{ number_format($summary['ips']) }}</div><div class="label">Unique IPs</div></div>
		<div class="card tile {{ $summary['errors'] > 0 ? 'bad' : '' }}"><div class="value">{{ number_format($summary['errors']) }}</div><div class="label">Exceptions</div></div>
		<div class="card tile {{ $summary['error_rate'] >= 1 ? 'bad' : '' }}"><div class="value">{{ $summary['error_rate'] }}%</div><div class="label">Exceptions per view</div></div>
		<div class="card tile"><div class="value">{{ number_format($summary['client_errors']) }}</div><div class="label">4xx responses</div></div>
		<div class="card tile {{ $summary['server_errors'] > 0 ? 'bad' : '' }}"><div class="value">{{ number_format($summary['server_errors']) }}</div><div class="label">5xx responses</div></div>
	</div>

	<div class="grid">
		<section class="card span-12">
			<h2>Traffic</h2>
			@include('statistics::partials.chart', ['series' => $series])
		</section>

		<section class="card span-8">
			<h2>Top exceptions</h2>
			@if ($topErrors->isEmpty())
				<div class="empty">No exceptions in this period. 🎉</div>
			@else
				<table>
					<thead><tr><th>Exception</th><th class="num">Count</th><th class="num">Last seen</th></tr></thead>
					<tbody>
						@foreach ($topErrors as $error)
							<tr>
								<td class="truncate">
									<a href="{{ route('statistics.show', $error->latest_id) }}" title="{{ $error->message }}">{{ $error->message !== '' ? $error->message : 'Unknown error' }}</a>
									<div class="mono muted truncate" style="max-width: none">{{ $error->errorFile }}:{{ $error->errorLine }}</div>
								</td>
								<td class="num"><span class="badge err">{{ number_format($error->total) }}</span></td>
								<td class="num muted">{{ \Carbon\Carbon::parse($error->last_seen)->diffForHumans() }}</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			@endif
		</section>

		<section class="card span-4">
			<h2>Status codes</h2>
			@php($maxStatus = max(1, (int) $statusCodes->max('total')))
			@forelse ($statusCodes as $status)
				<div style="display: flex; gap: 10px; align-items: center; margin-bottom: 8px">
					<div style="width: 48px">@include('statistics::partials.status', ['code' => $status->http_code])</div>
					<div style="flex: 1"><div class="bar" style="width: {{ round($status->total / $maxStatus * 100, 1) }}%"></div></div>
					<div class="num muted" style="width: 64px; text-align: right">
						<a href="{{ route('statistics.requests', ['period' => $metrics->period, 'status' => $status->http_code]) }}">{{ number_format($status->total) }}</a>
					</div>
				</div>
			@empty
				<div class="empty">No responses recorded.</div>
			@endforelse
		</section>

		<section class="card span-6">
			<h2>Top pages</h2>
			@if ($topRoutes->isEmpty())
				<div class="empty">No page views in this period.</div>
			@else
				<table>
					<thead><tr><th>Route</th><th class="num">Views</th><th class="num">4xx/5xx</th></tr></thead>
					<tbody>
						@foreach ($topRoutes as $route)
							<tr>
								<td class="truncate mono">
									<a href="{{ route('statistics.requests', ['period' => $metrics->period, 'q' => $route->route]) }}" title="{{ $route->route }}">{{ $route->route ?: '/' }}</a>
								</td>
								<td class="num">{{ number_format($route->hits) }}</td>
								<td class="num">
									@if ($route->failures > 0)
										<span class="badge s4">{{ number_format($route->failures) }}</span>
									@else
										<span class="muted">0</span>
									@endif
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			@endif
		</section>

		<section class="card span-6">
			<h2>Most active users</h2>
			@if ($topUsers->isEmpty())
				<div class="empty">No signed-in activity. Set <span class="mono">user_id</span> in <span class="mono">config/statistics.php</span> to track users.</div>
			@else
				<table>
					<thead><tr><th>User</th><th class="num">Views</th><th class="num">Last seen</th></tr></thead>
					<tbody>
						@foreach ($topUsers as $user)
							<tr>
								<td class="truncate">
									<a href="{{ route('statistics.requests', ['period' => $metrics->period, 'user' => $user->userid]) }}">{{ trim($user->firstname . ' ' . $user->lastname) ?: $user->userid }}</a>
									@if (trim($user->firstname . ' ' . $user->lastname) !== '')
										<span class="muted">· {{ $user->userid }}</span>
									@endif
								</td>
								<td class="num">{{ number_format($user->hits) }}</td>
								<td class="num muted">{{ \Carbon\Carbon::parse($user->last_seen)->diffForHumans() }}</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			@endif
		</section>
	</div>
@endsection
