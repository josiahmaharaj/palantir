{!! $summary !!}.

@foreach ($details as $label => $value)
{{ $label }}: {!! $value !!}
@endforeach
@if ($recordUrl)

View record:
{!! $recordUrl !!}
@endif
