@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name', 'Access Hub') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid h-full place-items-center bg-gray-50 px-4 text-gray-900 antialiased">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex flex-col items-center justify-center gap-1">
            <img src="{{ asset('BGC.png') }}" alt="Brookside Group of Companies" class="h-14 w-auto">
            <span class="text-lg font-semibold">Access Hub</span>
        </div>
        <x-flash />
        {{ $slot }}
    </div>
</body>
</html>
