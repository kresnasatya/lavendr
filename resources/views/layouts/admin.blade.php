<x-layouts::app.sidebar :title="$title ?? 'Admin Dashboard'">
    <flux:main>
        <div class="px-6 py-8">
            {{ $slot }}
        </div>
    </flux:main>
</x-layouts::app.sidebar>
