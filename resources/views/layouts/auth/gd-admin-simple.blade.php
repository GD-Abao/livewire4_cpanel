<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
    <div id="vanta-bg" class="pointer-events-none fixed inset-0 z-0"></div>
    <div class="relative z-10 flex min-h-svh flex-col items-center justify-center gap-6 bg-transparent p-6 md:p-10">
        <div class="flex w-full max-w-sm flex-col gap-2">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>

                <span class="flex  items-center justify-center rounded-md">
                    {{ env('APP_NAME') }}
                </span>
                <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
            </a>
            <div class="flex flex-col gap-6">
                {{ $slot }}
            </div>
        </div>
        <a href="https://www.great-good.tw" aria-label="鬼谷網頁設計" target="_blank">
            <flux:badge color="red">GD CMS</flux:badge>
        </a>
    </div>
    <script src="https://unpkg.com/p5@1.9.2/lib/p5.min.js"></script>
    <script src="https://unpkg.com/vanta@0.5.24/dist/vanta.topology.min.js"></script>
    <script>
        (() => {
            let vantaEffect = null;
            const initVanta = () => {
                const el = document.getElementById("vanta-bg");
                if (!el || !window.VANTA) return;

                if (vantaEffect) {
                    vantaEffect.destroy();
                }

                vantaEffect = VANTA.TOPOLOGY({
                    el,
                    mouseControls: true,
                    touchControls: true,
                    gyroControls: true,
                    minHeight: 200.0,
                    minWidth: 200.0,
                    scale: 1.0,
                    scaleMobile: 1.0,
                    color: 0x888168,
                    backgroundColor: 0x000000
                });
            };

            document.addEventListener("DOMContentLoaded", initVanta);
            document.addEventListener("livewire:navigated", initVanta);
        })();
    </script>
    @fluxScripts
</body>

</html>
