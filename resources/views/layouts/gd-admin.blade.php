<x-layouts::gd-admin.sidebar :title="$traitPageTitle ?? ($title ?? null)">
    <flux:main>
        {{ $slot }}
    </flux:main>
    <livewire:gd-session-message />
</x-layouts::gd-admin.sidebar>
