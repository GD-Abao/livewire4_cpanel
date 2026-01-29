<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.gd-admin')] class extends Component {
    //
};
?>

<div>
    <main class="max-w-5xl w-full mx-auto min-h-[calc(100dvh-80px)] px-4 py-20 text-zinc-900 dark:text-zinc-100">
        <section class="space-y-6">
            <header class="space-y-4">
                <h1 class="text-xl font-semibold">
                    <flux:icon.home />
                </h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">歡迎回來，{{ Auth::user()->email }}</p>
            </header>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <flux:card size="sm" class="space-y-1 text-zinc-900 dark:text-zinc-100">
                    <p class="text-xs uppercase tracking-wide text-zinc-400 dark:text-zinc-400">帳號</p>
                    <p class="text-sm">{{ Auth::user()->email }}</p>
                </flux:card>

                <flux:card size="sm" class="space-y-1 text-zinc-900 dark:text-zinc-100">
                    <p class="text-xs uppercase tracking-wide text-zinc-400 dark:text-zinc-400">IP</p>
                    <p class="text-sm">{{ request()->ip() }}</p>
                </flux:card>

                <flux:card size="sm" class="space-y-1 text-zinc-900 dark:text-zinc-100">
                    <p class="text-xs uppercase tracking-wide text-zinc-400 dark:text-zinc-400">本次登入時間</p>
                    <p class="text-sm">{{ now()->format('Y-m-d H:i') }}</p>
                </flux:card>
            </div>

            <p class="hidden lg:block text-xs text-zinc-500 dark:text-zinc-400">
                提示：請從左側選單開始管理。
            </p>
            <p class="lg:hidden block text-xs text-zinc-500 dark:text-zinc-400">
                提示：請點選左上角選單按鈕開始管理。
            </p>
        </section>
    </main>
</div>
