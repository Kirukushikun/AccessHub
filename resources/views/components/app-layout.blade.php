@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name', 'Access Hub') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">
<div class="min-h-full lg:flex" x-data="{ nav: false }">

    {{-- Sidebar --}}
    <div x-show="nav" x-cloak class="fixed inset-0 z-30 bg-gray-900/40 lg:hidden" @click="nav = false"></div>

    <aside
        class="fixed inset-y-0 left-0 z-40 w-64 shrink-0 -translate-x-full border-r border-gray-200 bg-white transition-transform lg:static lg:translate-x-0"
        :class="nav && '!translate-x-0'"
    >
        <div class="flex h-14 items-center gap-2 border-b border-gray-200 px-5">
            <span class="grid h-7 w-7 place-items-center rounded-md bg-gray-900 text-xs font-bold text-white">AH</span>
            <span class="font-semibold">Access Hub</span>
        </div>

        <nav class="flex flex-col gap-0.5 p-3">
            <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
            <x-nav-link :href="route('people.index')" :active="request()->routeIs('people.*')">People</x-nav-link>
            <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">Projects</x-nav-link>
            <x-nav-link :href="route('connections.index')" :active="request()->routeIs('connections.*')">Connections</x-nav-link>
            <x-nav-link :href="route('audit.index')" :active="request()->routeIs('audit.*')">Audit log</x-nav-link>
            <x-nav-link :href="route('admins.index')" :active="request()->routeIs('admins.*')">Admins</x-nav-link>
        </nav>

        <div class="absolute inset-x-0 bottom-0 border-t border-gray-200 p-3">
            @auth
                <p class="px-3 pb-1 text-xs text-gray-400">{{ auth()->user()->name }}</p>
            @endauth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full rounded-md px-3 py-2 text-left text-sm text-gray-600 hover:bg-gray-100">
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex h-14 items-center gap-3 border-b border-gray-200 bg-white px-4 lg:hidden">
            <button @click="nav = true" class="rounded-md p-1.5 text-gray-600 hover:bg-gray-100" aria-label="Open menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="font-semibold">Access Hub</span>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <x-flash />
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
