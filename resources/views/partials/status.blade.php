@php($code = (string) $code)
@if ($code !== '')
	<span class="badge s{{ substr($code, 0, 1) }}">{{ $code }}</span>
@else
	<span class="muted">—</span>
@endif
