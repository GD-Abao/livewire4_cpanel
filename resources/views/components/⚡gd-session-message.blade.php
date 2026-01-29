<?php

use Livewire\Component;

// 全域訊息元件：
// - 顯示後 2 秒自動關閉
// - 支援 session flash / Livewire dispatch / JS CustomEvent
new class extends Component
{
    // 是否顯示訊息視窗（預設不顯示，避免影響版面）
    public bool $show = false;

    // 訊息樣式（目前未套色，保留擴充用）
    public string $style = 'success';

    // 訊息內容
    public string $message = '';

    // DOM root id，給前端 JS 掛事件/observer
    public string $componentId;

    public function mount(): void
    {
        $this->componentId = 'session-message-'.uniqid('', false);
        $sessionMessage = session('gd-session-message');

        // 若頁面是 redirect 後的 flash 訊息，載入並顯示
        if (! empty($sessionMessage)) {
            $this->style = session('flash.bannerStyle', 'success');
            $this->message = (string) $sessionMessage;
            $this->show = true;
        }
    }

    // 由 JS 或 Livewire 事件呼叫，立即顯示訊息
    public function showMessage(string $style, string $message): void
    {
        if ($message === '') {
            return;
        }

        $this->style = $style ?: 'success';
        $this->message = $message;
        $this->show = true;
    }

    // 關閉訊息視窗
    public function close(): void
    {
        $this->show = false;
    }
};
?>

<div id="{{ $componentId }}" data-session-message-root style="display: contents;">
    @if ($show && $message)
        <div class="fixed inset-0 z-50" data-session-message-panel>
            <!-- 點擊背景區域關閉訊息 -->
            <div class="absolute inset-0" wire:click="close"></div>

            <!-- 訊息顯示區域 -->
            <div class="absolute bottom-16 w-full max-w-fit right-8" wire:click="close">
                <div class="flex justify-center w-full mx-auto px-10 text-2xl py-2 rounded bg-zinc-200 dark:bg-zinc-950">
                    {{ $message }}
                </div>
            </div>
        </div>
    @endif
</div>

@script
    <script>
        const componentId = @js($componentId);
        const root = document.getElementById(componentId);

        if (root && root.dataset.sessionMessageInitialized !== '1') {
            root.dataset.sessionMessageInitialized = '1';

            let closeTimer = null;

            // 排程自動關閉（每次顯示都重新計時）
            const scheduleClose = () => {
                if (closeTimer) {
                    clearTimeout(closeTimer);
                }
                closeTimer = setTimeout(() => {
                    $wire.close();
                    closeTimer = null;
                }, 2000);
            };

            // 同步訊息內容到 Livewire，並啟動倒數
            const handleMessage = (detail) => {
                const message = detail?.message ?? '';
                if (!message) return;

                const style = detail?.style ?? 'success';
                $wire.showMessage(style, message);
                scheduleClose();
            };

            const onBannerMessage = (event) => handleMessage(event?.detail);

            // 1) 監聽瀏覽器事件（window.dispatchEvent(new CustomEvent(...))）
            window.addEventListener('banner-message', onBannerMessage);

            // 2) 監聽 Livewire 事件（$this->dispatch('banner-message', ...)）
            document.addEventListener('livewire:init', () => {
                if (window.Livewire && typeof window.Livewire.on === 'function') {
                    window.Livewire.on('banner-message', handleMessage);
                }
            }, { once: true });

            // 頁面載入時若已有訊息（session flash），也套用自動關閉。
            if (root.querySelector('[data-session-message-panel]')) {
                scheduleClose();
            }

            const observer = new MutationObserver(() => {
                if (!document.body.contains(root)) {
                    if (closeTimer) {
                        clearTimeout(closeTimer);
                        closeTimer = null;
                    }
                    window.removeEventListener('banner-message', onBannerMessage);
                    observer.disconnect();
                }
            });

            observer.observe(document.body, { childList: true, subtree: true });
        }
    </script>
@endscript
