@props(['title'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="dark">
    <title>{{ $title }} - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    @vite('resources/css/app.css')
</head>
<body class="min-h-screen bg-zinc-950 font-sans text-zinc-50 antialiased">
    <div class="mx-auto flex min-h-screen w-full max-w-lg flex-col justify-center gap-8 px-4 py-10">
        <header class="flex items-center justify-center gap-3">
            <span class="size-5 rounded-full border-4 border-amber-500" aria-hidden="true"></span>
            <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
        </header>

        <main class="overflow-hidden rounded-2xl bg-zinc-900 ring-1 ring-white/10">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
