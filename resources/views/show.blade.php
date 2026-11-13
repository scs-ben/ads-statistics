@extends('statistics::layout')

@section('title', $statistic->errorMessage !== null ? 'Exception #' . $statistic->id : 'Request #' . $statistic->id)

@section('content')
	<p class="sub" style="margin-bottom: 8px"><a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('statistics.requests') }}">← Back</a></p>

	@if ($statistic->errorMessage !== null)
		<h1>{{ \Ads\Statistics\Dashboard\Metrics::firstLine($statistic->errorMessage, 300) ?: 'Unknown error' }}</h1>
		<p class="sub mono">{{ $statistic->errorFile }}:{{ $statistic->errorLine }}
			@if ($similar > 1)
				· <a href="{{ route('statistics.requests', ['type' => 'errors', 'q' => $statistic->errorFile, 'period' => '30d']) }}">{{ number_format($similar) }} occurrences</a>
			@endif
		</p>
	@else
		<h1 class="mono">{{ $statistic->method }} {{ $statistic->destination_url ?: '/' }}</h1>
		<p class="sub">Request #{{ $statistic->id }}</p>
	@endif

	<div class="grid">
		<section class="card span-4">
			<h2>Details</h2>
			<dl>
				<dt>Time</dt><dd>{{ $statistic->created_at }}</dd>
				<dt>Status</dt><dd>@include('statistics::partials.status', ['code' => $statistic->http_code])</dd>
				<dt>Method</dt><dd class="mono">{{ $statistic->method ?: '—' }}</dd>
				<dt>URL</dt><dd class="mono">{{ $statistic->destination_url ?: '—' }}</dd>
				<dt>Route</dt><dd class="mono">{{ $statistic->destination_name ?: '—' }}</dd>
				<dt>Referer</dt><dd class="mono">{{ $statistic->referer_url ?: '—' }}</dd>
				<dt>User</dt>
				<dd>
					@if ($statistic->userid)
						<a href="{{ route('statistics.requests', ['user' => $statistic->userid, 'period' => '30d']) }}">{{ trim($statistic->firstname . ' ' . $statistic->lastname) ?: $statistic->userid }}</a>
						<span class="muted">· {{ $statistic->userid }}</span>
					@else
						<span class="muted">Guest</span>
					@endif
				</dd>
				<dt>IP</dt><dd class="mono">{{ $statistic->ip_address ?: '—' }}</dd>
			</dl>
		</section>

		<section class="card span-8">
			<h2>Input</h2>
			@if ($input)
				<pre class="mono">{{ $input }}</pre>
			@else
				<div class="empty">No input recorded.</div>
			@endif
		</section>

		@if ($statistic->errorMessage !== null)
			<section class="card span-12">
				<h2>Message &amp; stack trace</h2>
				<pre class="mono">{{ $statistic->errorMessage }}</pre>
			</section>
		@endif
	</div>
@endsection
