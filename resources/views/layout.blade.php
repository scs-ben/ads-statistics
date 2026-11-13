<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title>@yield('title', 'Statistics') · {{ config('app.name') }}</title>
	<style>
		:root {
			--bg: #f4f5f7; --card: #fff; --border: #e4e6eb; --text: #1d2129; --muted: #6b7280;
			--accent: #4f46e5; --accent-soft: #e0e7ff; --danger: #dc2626; --danger-soft: #fee2e2;
			--warn: #b45309; --warn-soft: #fef3c7; --ok: #047857; --ok-soft: #d1fae5; --code: #f8f9fb;
		}
		@media (prefers-color-scheme: dark) {
			:root {
				--bg: #0f1115; --card: #171a21; --border: #262a33; --text: #e5e7eb; --muted: #9ca3af;
				--accent: #818cf8; --accent-soft: #1e1b4b; --danger: #f87171; --danger-soft: #3b1414;
				--warn: #fbbf24; --warn-soft: #3a2a07; --ok: #34d399; --ok-soft: #062e22; --code: #11141a;
			}
		}
		* { box-sizing: border-box; }
		body { margin: 0; background: var(--bg); color: var(--text); font: 14px/1.5 ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
		a { color: var(--accent); text-decoration: none; }
		a:hover { text-decoration: underline; }
		header { display: flex; flex-wrap: wrap; gap: 12px 24px; align-items: center; padding: 14px 24px; background: var(--card); border-bottom: 1px solid var(--border); }
		header .brand { font-weight: 700; font-size: 16px; color: var(--text); }
		header nav { display: flex; gap: 4px; }
		header nav a { padding: 6px 12px; border-radius: 6px; color: var(--muted); font-weight: 500; }
		header nav a.active, header nav a:hover { background: var(--accent-soft); color: var(--accent); text-decoration: none; }
		header .tools { margin-left: auto; display: flex; gap: 12px; align-items: center; color: var(--muted); }
		select, input[type=text] { font: inherit; color: var(--text); background: var(--card); border: 1px solid var(--border); border-radius: 6px; padding: 5px 8px; }
		button { font: inherit; cursor: pointer; color: #fff; background: var(--accent); border: 0; border-radius: 6px; padding: 6px 14px; }
		main { padding: 24px; max-width: 1400px; margin: 0 auto; }
		h1 { font-size: 20px; margin: 0 0 4px; }
		.sub { color: var(--muted); margin: 0 0 20px; }
		.grid { display: grid; gap: 16px; grid-template-columns: repeat(12, 1fr); }
		.card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 16px 18px; min-width: 0; }
		.card h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); margin: 0 0 12px; font-weight: 600; }
		.span-12 { grid-column: span 12; } .span-8 { grid-column: span 8; } .span-6 { grid-column: span 6; } .span-4 { grid-column: span 4; }
		.tiles { display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); margin-bottom: 16px; }
		.tile .value { font-size: 26px; font-weight: 700; font-variant-numeric: tabular-nums; }
		.tile .label { color: var(--muted); font-size: 12px; }
		.tile.bad .value { color: var(--danger); }
		table { width: 100%; border-collapse: collapse; }
		th { text-align: left; font-weight: 600; color: var(--muted); font-size: 12px; padding: 6px 8px; border-bottom: 1px solid var(--border); white-space: nowrap; }
		td { padding: 7px 8px; border-bottom: 1px solid var(--border); vertical-align: top; }
		tr:last-child td { border-bottom: 0; }
		td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
		.truncate { max-width: 0; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
		.mono { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 12.5px; }
		.muted { color: var(--muted); }
		.badge { display: inline-block; padding: 1px 7px; border-radius: 999px; font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums; background: var(--accent-soft); color: var(--accent); }
		.badge.s2 { background: var(--ok-soft); color: var(--ok); }
		.badge.s3 { background: var(--accent-soft); color: var(--accent); }
		.badge.s4 { background: var(--warn-soft); color: var(--warn); }
		.badge.s5, .badge.err { background: var(--danger-soft); color: var(--danger); }
		.bar { height: 6px; border-radius: 3px; background: var(--accent); min-width: 2px; }
		.empty { color: var(--muted); padding: 24px 0; text-align: center; }
		.chart svg { width: 100%; height: 180px; display: block; }
		.chart .legend { display: flex; gap: 16px; font-size: 12px; color: var(--muted); margin-top: 8px; }
		.chart .legend i { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 6px; vertical-align: -1px; }
		.filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 16px; }
		pre { background: var(--code); border: 1px solid var(--border); border-radius: 8px; padding: 12px; overflow: auto; max-height: 520px; margin: 0; white-space: pre-wrap; word-break: break-word; }
		dl { display: grid; grid-template-columns: max-content 1fr; gap: 6px 16px; margin: 0; }
		dt { color: var(--muted); } dd { margin: 0; word-break: break-all; }
		.pagination { display: flex; gap: 12px; justify-content: space-between; align-items: center; margin-top: 16px; color: var(--muted); }
		@media (max-width: 1000px) { .span-8, .span-6, .span-4 { grid-column: span 12; } }
		@media (max-width: 640px) { main { padding: 16px; } header { padding: 12px 16px; } header .tools { margin-left: 0; } }
	</style>
</head>
<body>
	<header>
		<a class="brand" href="{{ route('statistics.dashboard') }}">{{ config('app.name') }} Statistics</a>
		<nav>
			<a href="{{ route('statistics.dashboard', ['period' => $metrics->period ?? null]) }}" class="{{ request()->routeIs('statistics.dashboard') ? 'active' : '' }}">Overview</a>
			<a href="{{ route('statistics.requests', ['period' => $metrics->period ?? null]) }}" class="{{ request()->routeIs('statistics.requests') && request('type') !== 'errors' ? 'active' : '' }}">Requests</a>
			<a href="{{ route('statistics.requests', ['period' => $metrics->period ?? null, 'type' => 'errors']) }}" class="{{ request()->routeIs('statistics.show') || request('type') === 'errors' ? 'active' : '' }}">Exceptions</a>
		</nav>
		@isset($metrics)
			<form class="tools" method="get">
				@foreach (request()->except(['period', 'page']) as $key => $value)
					@if (is_string($value))
						<input type="hidden" name="{{ $key }}" value="{{ $value }}">
					@endif
				@endforeach
				<select name="period" onchange="this.form.submit()" aria-label="Period">
					@foreach (\Ads\Statistics\Dashboard\Metrics::PERIODS as $key => $period)
						<option value="{{ $key }}" {{ $metrics->period === $key ? 'selected' : '' }}>{{ $period[0] }}</option>
					@endforeach
				</select>
				<label><input type="checkbox" id="auto-refresh"> Auto-refresh</label>
			</form>
		@endisset
	</header>

	<main>
		@yield('content')
	</main>

	<script>
		(function () {
			var box = document.getElementById('auto-refresh');
			if (!box) return;
			var key = 'statistics.autoRefresh', timer;
			function schedule() {
				clearTimeout(timer);
				if (box.checked) timer = setTimeout(function () { location.reload(); }, 30000);
			}
			try { box.checked = localStorage.getItem(key) === '1'; } catch (e) {}
			box.addEventListener('change', function () {
				try { localStorage.setItem(key, box.checked ? '1' : '0'); } catch (e) {}
				schedule();
			});
			schedule();
		})();
	</script>
</body>
</html>
