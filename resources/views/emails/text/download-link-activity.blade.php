{!! $summary !!}.

@foreach ($details as $label => $value)
{{ $label }}: {!! $value !!}
@endforeach
@if ($logUrl)

View video log:
{!! $logUrl !!}
@endif
