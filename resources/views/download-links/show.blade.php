<x-layouts.download title="Your file is ready">
    <div class="relative flex aspect-video items-center justify-center bg-black" aria-hidden="true">
        <span class="absolute top-4 left-4 size-6 border-t-2 border-l-2 border-zinc-600"></span>
        <span class="absolute top-4 right-4 size-6 border-t-2 border-r-2 border-zinc-600"></span>
        <span class="absolute bottom-4 left-4 size-6 border-b-2 border-l-2 border-zinc-600"></span>
        <span class="absolute right-4 bottom-4 size-6 border-r-2 border-b-2 border-zinc-600"></span>
        <span class="flex size-28 items-center justify-center rounded-full border-[10px] border-amber-500 text-lg font-semibold text-zinc-50">
            {{ $format }}
        </span>
    </div>

    <div class="flex flex-col gap-6 p-6 sm:p-8">
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl leading-tight font-semibold tracking-tight text-balance break-words">{{ $downloadLink->title }}</h1>
            <p class="text-sm break-all text-zinc-400">{{ $fileName }}</p>
        </div>

        <dl class="divide-y divide-white/10 border-y border-white/10 text-sm">
            @foreach ($details as $label => $value)
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-zinc-400">{{ $label }}</dt>
                    <dd class="text-right font-medium">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <form id="download-form" method="POST" action="{{ route('download-link.file', $downloadLink->link) }}" class="flex flex-col gap-3">
            @csrf
            <button type="submit" class="w-full cursor-pointer rounded-xl bg-amber-500 px-6 py-4 text-base font-semibold text-zinc-950 transition-colors hover:bg-amber-400 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-400">
                Download
            </button>
            <p id="download-status" role="status" class="text-center text-sm text-zinc-400">
                Available until {{ $downloadLink->expires_at->format('F j, Y \a\t g:i A') }}
            </p>
        </form>
    </div>

    <script>
        document.getElementById('download-form').addEventListener('submit', () => {
            document.getElementById('download-status').textContent = 'Your download is starting. Large files can take a moment to appear.';
        });
    </script>
</x-layouts.download>
