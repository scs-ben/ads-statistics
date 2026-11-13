@extends('statistics::layout')

@section('title', $filters['type'] === 'errors' ? 'Exceptions' : 'Requests')

@section('content')
	<h1>{{ $filters['type'] === 'errors' ? 'Exceptions' : 'Requests' }}</h1>
	<p class="sub">{{ $metrics->label() }} · {{ number_format($rows->total()) }} matching rows</p>

	<form class="filters" method="get">
		<input type="hidden" name="period" value="{{ $metrics->period }}">
		<select name="type" aria-label="Type">
			<option value="all" {{ $filters['type'] === 'all' ? 'selected' : '' }}>Everything</option>
			<option value="views" {{ $filters['type'] === 'views' ? 'selected' : '' }}>Page views</option>
			<option value="errors" {{ $filters['type'] === 'errors' ? 'selected' : '' }}>Exceptions</option>
		</select>
		<input type="text" name="q" value="{{ $filters['q'] }}" placeholder="URL, route or file contains…" aria-label="Search" style="min-width: 240px">
		<input type="text" name="user" value="{{ $filters['user'] }}" placeholder="User ID" aria-label="User ID">
		<input type="text" name="status" value="{{ $filters['status'] }}" placeholder="Status (404, 5xx)" aria-label="Status" style="width: 130px">
		<button type="submit">Filter</button>
		@if ($filters['q'] !== '' || $filters['user'] !== '' || $filters['status'] !== '' || $filters['type'] !== 'all')
			<a href="{{ route('statistics.requests', ['period' => $metrics->period]) }}">Clear</a>
		@endif
	</form>

	<section class="card">
		@if ($rows->isEmpty())
			<div class="empty">Nothing matches these filters.</div>
		@else
			<div style="overflow-x: auto">
				<table>
					<thead>
						<tr><th>Time</th><th>Status</th><th>Method</th><th>Request</th><th>User</th><th>IP</th></tr>
					</thead>
					<tbody>
						@foreach ($rows as $row)
							<tr>
								<td class="muted" style="white-space: nowrap" title="{{ $row->created_at }}">{{ optional($row->created_at)->format('M j H:i:s') }}</td>
								<td>@include('statistics::partials.status', ['code' => $row->http_code])</td>
								<td class="mono muted">{{ $row->method }}</td>
								<td class="truncate" style="min-width: 260px">
									@if ($row->errorMessage !== null)
										<a href="{{ route('statistics.show', $row->id) }}" title="{{ \Ads\Statistics\Dashboard\Metrics::firstLine($row->errorMessage, 500) }}">
											<span class="badge err">Exception</span> {{ \Ads\Statistics\Dashboard\Metrics::firstLine($row->errorMessage) }}
										</a>
										<div class="mono muted truncate" style="max-width: none">{{ $row->errorFile }}:{{ $row->errorLine }}</div>
									@else
										<a href="{{ route('statistics.show', $row->id) }}" class="mono" title="{{ $row->destination_url }}">{{ $row->destination_url ?: '/' }}</a>
										@if ($row->destination_name)
											<span class="muted">· {{ $row->destination_name }}</span>
										@endif
									@endif
								</td>
								<td style="white-space: nowrap">
									@if ($row->userid)
										<a href="{{ route('statistics.requests', array_merge(request()->except('page'), ['user' => $row->userid])) }}">{{ trim($row->firstname . ' ' . $row->lastname) ?: $row->userid }}</a>
									@else
										<span class="muted">Guest</span>
									@endif
								</td>
								<td class="mono muted">{{ $row->ip_address }}</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			<div class="pagination">
				<span>Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }}</span>
				<span style="display: flex; gap: 12px">
					@if ($rows->previousPageUrl())<a href="{{ $rows->previousPageUrl() }}">← Newer</a>@endif
					@if ($rows->nextPageUrl())<a href="{{ $rows->nextPageUrl() }}">Older →</a>@endif
				</span>
			</div>
		@endif
	</section>
@endsection
