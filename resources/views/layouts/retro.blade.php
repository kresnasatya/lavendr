<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Lavendr') }} - Vending Machine</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">

    <!-- Retro Styles -->
    @vite(['resources/css/retro.css', 'resources/js/retro.js'])

    <!-- Livewire Styles -->
    @livewireStyles

    <style>
        /* Inline fallback for fonts */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen retro-screen font-pixel text-retro-darkest bg-retro-lightest">
    <!-- Retro Header -->
    <header class="pixel-border bg-retro-light p-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center">
                <!-- Logo & Title -->
                <div class="flex items-center gap-4">
                    <div class="text-3xl">🕹️</div>
                    <div>
                        <h1 class="text-lg leading-tight">VENDING MACHINE</h1>
                        <p class="font-terminal text-sm text-retro-dark mt-1">
                            {{ Auth::user()->name }}
                        </p>
                    </div>
                </div>

                <!-- Balance Display -->
                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <p class="text-xs text-retro-dark mb-1">BALANCE</p>
                        <p class="text-xl text-retro-darkest" x-data="{ balance: $el.dataset.balance }"
                            data-balance="{{ auth()->user()->balance?->current_balance ?? 0 }}">
                            <span x-text="balance"></span> PTS
                        </p>
                    </div>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button
                            @click="open = !open"
                            class="retro-button text-xs"
                            aria-label="User menu"
                        >
                            MENU
                        </button>

                        <div
                            x-show="open"
                            @click.away="open = false"
                            x-transition
                            class="absolute right-0 mt-2 w-48 pixel-border bg-retro-lightest z-50"
                            x-cloak
                        >
                            <div class="p-2 space-y-1">
                                <a
                                    href="{{ route('profile.edit') }}"
                                    class="block px-3 py-2 text-sm font-terminal hover:bg-retro-light rounded"
                                >
                                    PROFILE
                                </a>
                                @if(Auth::user()->hasRole(['manager', 'superadmin']))
                                    <a
                                        href="{{ route('admin.dashboard') }}"
                                        class="block px-3 py-2 text-sm font-terminal hover:bg-retro-light rounded"
                                    >
                                        ADMIN
                                    </a>
                                @endif
                                <hr class="border-retro-dark my-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="w-full text-left block px-3 py-2 text-sm font-terminal hover:bg-retro-light rounded text-red-600"
                                    >
                                        LOGOUT
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>

    <!-- Retro Footer -->
    <footer class="fixed bottom-0 left-0 right-0 pixel-border bg-retro-dark text-retro-lightest p-3">
        <div class="max-w-7xl mx-auto flex justify-between items-center text-xs font-terminal">
            <p>◆ PRESS A BUTTON TO SELECT</p>
            <p>START TO CONFIRM ◆</p>
        </div>
    </footer>

    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- Screen Reader Announcements -->
    <div aria-live="polite" class="sr-only">
        {{ $slot ?? '' }}
    </div>
</body>
</html>
