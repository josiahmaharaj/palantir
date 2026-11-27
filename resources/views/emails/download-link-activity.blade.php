<x-emails.layout :heading="$heading" :preheader="$summary">
    <p style="margin:0 0 24px; font-size:15px; line-height:1.6; color:#3f3f46;">{{ $summary }}.</p>

    <x-emails.details :rows="$details" />

    @if ($recordUrl)
        <x-emails.button :url="$recordUrl">View record</x-emails.button>
    @endif
</x-emails.layout>
