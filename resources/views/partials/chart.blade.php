@php
	$width = 1000;
	$height = 180;
	$count = max(count($series), 1);
	$max = max(1, max(array_map(function ($point) { return $point['views'] + $point['errors']; }, $series ?: [['views' => 0, 'errors' => 0]])));
	$slot = $width / $count;
	$gap = $slot > 6 ? 1.5 : 0.5;
	$total = array_sum(array_column($series, 'views')) + array_sum(array_column($series, 'errors'));
@endphp

<div class="chart">
	@if ($total === 0)
		<div class="empty">No traffic in this period.</div>
	@else
		<svg viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" role="img" aria-label="Page views and exceptions over time">
			<line x1="0" y1="{{ $height - 0.5 }}" x2="{{ $width }}" y2="{{ $height - 0.5 }}" stroke="var(--border)" />
			@foreach ($series as $i => $point)
				@php
					$x = $i * $slot + $gap / 2;
					$w = max($slot - $gap, 0.5);
					$viewsHeight = $point['views'] / $max * ($height - 8);
					$errorsHeight = $point['errors'] / $max * ($height - 8);
				@endphp
				<g>
					<title>{{ $point['label'] }} — {{ number_format($point['views']) }} views, {{ number_format($point['errors']) }} exceptions</title>
					<rect x="{{ $x }}" y="0" width="{{ $w }}" height="{{ $height }}" fill="transparent" />
					@if ($viewsHeight > 0)
						<rect x="{{ $x }}" y="{{ $height - $viewsHeight - $errorsHeight }}" width="{{ $w }}" height="{{ $viewsHeight }}" fill="var(--accent)" opacity="0.85" />
					@endif
					@if ($errorsHeight > 0)
						<rect x="{{ $x }}" y="{{ $height - $errorsHeight }}" width="{{ $w }}" height="{{ $errorsHeight }}" fill="var(--danger)" />
					@endif
				</g>
			@endforeach
		</svg>
		<div class="legend">
			<span><i style="background: var(--accent)"></i>Page views</span>
			<span><i style="background: var(--danger)"></i>Exceptions</span>
			<span style="margin-left: auto">{{ $series[0]['label'] }} → {{ end($series)['label'] }} · peak {{ number_format($max) }}</span>
		</div>
	@endif
</div>
