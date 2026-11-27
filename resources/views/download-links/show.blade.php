<x-layouts.app title="Your file is ready">
    <main class="flex min-h-screen items-center justify-center p-4">
        <div class="flex w-full max-w-md flex-col gap-6 rounded-2xl bg-white p-8 text-center shadow-xl dark:bg-gray-800">
            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Your file is ready</h1>
                <p class="text-gray-600 dark:text-gray-300">{{ $downloadLink->title }}</p>
            </div>

            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <p class="font-medium break-all text-gray-900 dark:text-white">{{ $fileName }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $fileSize }}</p>
            </div>

            <form method="POST" action="{{ route('download-link.file', $downloadLink->link) }}">
                @csrf
                <button type="submit" class="w-full cursor-pointer rounded-lg bg-indigo-600 px-6 py-3 font-semibold text-white hover:bg-indigo-700">
                    Download
                </button>
            </form>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                This link expires on {{ $downloadLink->expires_at->format('F j, Y \\a\\t g:i A') }}.
            </p>
        </div>
    </main>
</x-layouts.app>
