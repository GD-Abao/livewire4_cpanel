<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.gd-admin-head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            {{-- GD-Admin Logo，連結到後台首頁 --}}
            <h1>{{ env('APP_NAME') }}</h1>
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group  class="grid">
                {{-- 後台 Menu列表 --}}
                @foreach (app('gdAdminMenu') as $menu)
                    @if (!$menu['show'])
                        @continue
                    @endif

                    @if (empty($menu['children']))
                        <flux:sidebar.item  icon="{{ $menu['icon'] ?? 'dot' }}" :href="route('gd-admin.' . $menu['route'])"
                            :current="request()->routeIs('gd-admin.' . $menu['route'])" wire:navigate>
                            {{ $menu['title'] }}
                        </flux:sidebar.item>
                    @else
                        <flux:sidebar.group expandable :expanded="$menu['expanded'] ?? false" :heading="$menu['title']">
                            @foreach ($menu['children'] as $child)
                                @if (!($child['show'] ?? true))
                                    @continue
                                @endif
                                <flux:sidebar.item :href="route('gd-admin.' . $child['route'])"
                                    :current="request()->routeIs('gd-admin.' . $child['route'])" wire:navigate>
                                    {{ $child['title'] }}
                                </flux:sidebar.item>
                            @endforeach
                        </flux:sidebar.group>
                    @endif
                @endforeach
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        {{-- 桌面版用戶選單 --}}
        <x-gd-admin.desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    {{-- 後台設定頁 --}}
                    <flux:menu.item :href="route('gd-admin.settings.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                {{-- 後台登出 --}}
                <form method="POST" action="{{ route('gd-admin.logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @stack('scripts')
    @fluxScripts
</body>

</html>
