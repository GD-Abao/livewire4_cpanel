<?php

use Illuminate\Support\Str;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Livewire\WithFileUploads;

// 將編輯器隔離，避免父層驗證/重繪時把 TinyMCE 一起拆掉。
new #[Isolate] class extends Component {
    use WithFileUploads;

    // Livewire 綁定值（對應前端 model = 'value'）。
    #[Modelable]
    public $value = '';

    // editorId: textarea 的 id；editorRef: Livewire ref 名稱（供 this.$refs 使用）。
    public ?string $editorId = null;

    public ?string $editorRef = null;

    // 上傳限制：單位為 KB。
    public int $maxUploadSize = 5120;

    // 允許的圖片 MIME 類型。
    public array $allowedUploadTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public $tinyMceUpload;

    public function mount(): void
    {
        $htmlAttributes = $this->getHtmlAttributes();

        // 依序嘗試：已傳入 editorRef -> 已傳入 editorId -> textarea 的 id -> 自動生成。
        if (!$this->editorRef) {
            $this->editorRef = $this->editorId ?: $htmlAttributes['id'] ?? null ?: 'tinymce-' . str_replace('.', '-', uniqid('', true));
        }

        // editorId 沒給就沿用 editorRef，確保 DOM 有唯一 id。
        if (!$this->editorId) {
            $this->editorId = $this->editorRef;
        }
    }

    public function storeTinyMceImage(): ?string
    {
        if (!$this->tinyMceUpload) {
            return null;
        }

        // 後端驗證：避免不合法的檔案進儲存桶。
        $this->validate(
            [
                'tinyMceUpload' => ['image', "max:{$this->maxUploadSize}"],
            ],
            [
                'tinyMceUpload.image' => '檔案必須是圖片格式',
                'tinyMceUpload.max' => '圖片大小不可超過 ' . $this->maxUploadSize / 1024 . 'MB',
            ],
        );

        $date = now()->format('Ymd');
        $imageName = now()->format('His') . '-' . Str::random(10) . '.' . $this->tinyMceUpload->extension();
        $path = $this->tinyMceUpload->storeAs('tinyMceFiles/' . $date, $imageName, 'public');

        // 上傳完成後清掉暫存。
        $this->tinyMceUpload = null;

        return asset('storage/' . $path);
    }
};
?>

@php
    // 正規化 attributes，避免空值造成錯誤。
    $attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
    $attributeList = $attributes->getAttributes();
    $modelAttribute = null;

    // 取得第一個 wire:model* 屬性（含 lazy/defer/debounce 等修飾子）。
    foreach ($attributeList as $key => $value) {
        if (str_starts_with($key, 'wire:model')) {
            $modelAttribute = $key;
            break;
        }
    }

    // 解析修飾子，供同步策略使用（live/lazy/defer/debounce）。
    $modelModifiers = $modelAttribute ? array_slice(explode('.', $modelAttribute), 1) : [];

    // 將不該直接輸出到 textarea 的屬性移除（避免 wire:model 重複綁定）。
    // 補上 w-full，避免 RWD 時編輯器寬度被內容擠窄。
    $inputAttributes = $attributes
        ->whereDoesntStartWith('wire:')
        ->except('id')
        ->merge(['class' => 'w-full']);
@endphp

<!-- TinyMCE 編輯器（DOM 由 TinyMCE 接管） -->

<div wire:ignore>
    <textarea id="{{ $editorId }}" wire:ref="{{ $editorRef }}" {{ $inputAttributes }}></textarea>
</div>

<!-- 修正 TinyMCE 浮動面板寬度 -->
@push('styles')
    <style>
        /* 確保 TinyMCE 在 RWD 下維持 100% 寬度 */
        .tox.tox-tinymce {
            width: 100% !important;
            max-width: 100% !important;
        }

        .tox .tox-editor-container {
            min-width: 0 !important;
        }

        .tox-tinymce-aux {
            width: auto !important;
        }
    </style>
@endpush

@assets
    <script src="/js/tinymce/tinymce.min.js"></script>
@endassets

@script
    <script>
        const editorId = @js($editorId);
        const refName = @js($editorRef);
        const model = 'value';
        const modifiers = @js($modelModifiers);
        const allowedTypes = @js($allowedUploadTypes);
        const maxSizeKB = @js($maxUploadSize);
        // 優先用 Livewire ref 取得 textarea；沒有 ref 才 fallback 到 id。
        const textarea = this.$refs?.[refName] ?? document.getElementById(editorId);

        // 避免重複初始化（Livewire 重新渲染時會再次執行）。
        if (textarea && textarea.dataset.tinymceInitialized !== '1') {
            textarea.dataset.tinymceInitialized = '1';

            // 同步策略會尊重 wire:model 修飾子（live/lazy/defer/debounce）。
            const isLazy = modifiers.includes('lazy');
            const isDefer = modifiers.includes('defer');
            const isLive = modifiers.includes('live') || (!isLazy && !isDefer);
            const syncLive = !isDefer;
            const maxSizeMB = maxSizeKB / 1024;
            let editorInstance = null;
            let lastValue = null;
            let lastSentValue = null;
            let syncTimer = null;
            const cleanupTasks = [];

            // 收集所有清理行為，DOM 被移除時一口氣執行。
            const addCleanup = (fn) => cleanupTasks.push(fn);
            addCleanup(() => {
                if (syncTimer) {
                    clearTimeout(syncTimer);
                    syncTimer = null;
                }
            });

            // 支援 wire:model.debounce.500ms / .2s 的寫法。
            const debounceMs = (() => {
                const index = modifiers.indexOf('debounce');

                if (index === -1) return 150;

                const raw = modifiers[index + 1] ?? '';
                const parsed = parseInt(raw, 10);

                if (Number.isFinite(parsed)) {
                    return raw.endsWith('s') && !raw.endsWith('ms') ? parsed * 1000 : parsed;
                }

                return 150;
            })();

            // setLocal：只更新 Livewire 本地狀態（不送出網路請求）。
            const setLocal = (value) => {
                const nextValue = value ?? '';
                if (nextValue === lastValue) return;

                lastValue = nextValue;
                $wire.$set(model, nextValue, false);
            };

            // commitNetwork：立即送出 Livewire 更新（若 syncLive=true）。
            const commitNetwork = (value) => {
                if (!syncLive) return;

                const nextValue = value ?? '';
                if (nextValue === lastSentValue) return;

                lastSentValue = nextValue;
                $wire.$set(model, nextValue, true);
            };

            // queueNetwork：依 debounce 設定延後送出，避免輸入過頻。
            const queueNetwork = (value) => {
                if (!syncLive) return;

                if (syncTimer) {
                    clearTimeout(syncTimer);
                }

                syncTimer = setTimeout(() => {
                    commitNetwork(value);
                    syncTimer = null;
                }, debounceMs);
            };

            // flushSync：強制把編輯器內容寫回 Livewire（可選 commit）。
            const flushSync = ({
                commit = false
            } = {}) => {
                if (syncTimer) {
                    clearTimeout(syncTimer);
                    syncTimer = null;
                }

                if (editorInstance) {
                    const content = editorInstance.getContent();
                    setLocal(content);

                    if (commit) {
                        commitNetwork(content);
                    }
                }
            };

            // 表單送出前強制同步，避免 defer 狀態漏送。
            const bindFormSubmit = () => {
                const form = textarea.closest('form');
                if (!form) return;

                const onSubmit = () => flushSync();

                form.addEventListener('submit', onSubmit);
                addCleanup(() => form.removeEventListener('submit', onSubmit));
            };

            // 統一錯誤訊息出口（交給全域 banner-message 顯示）。
            const notifyError = (message) => {
                if (!message) return;

                window.dispatchEvent(new CustomEvent('banner-message', {
                    detail: {
                        style: 'danger',
                        message,
                    },
                }));
            };

            // TinyMCE 圖片上傳流程：
            // 1) 前端檢查類型/大小
            // 2) Livewire upload 暫存
            // 3) 呼叫後端 storeTinyMceImage 取得 URL
            const ImagePost = (blobInfo, progress) => new Promise((resolve, reject) => {
                const blob = blobInfo.blob();
                const file = new File([blob], blobInfo.filename(), {
                    type: blob.type
                });

                // 檢查檔案類型
                if (!allowedTypes.includes(file.type)) {
                    const message = '僅支援 JPEG、PNG、GIF 或 WebP 格式';
                    notifyError(message);
                    reject({
                        message,
                        remove: true
                    });
                    return;
                }

                // 檢查檔案大小
                if (file.size > maxSizeKB * 1024) {
                    const message = `圖片大小不可超過 ${maxSizeMB}MB`;
                    notifyError(message);
                    reject({
                        message,
                        remove: true
                    });
                    return;
                }

                $wire.upload(
                    'tinyMceUpload',
                    file,
                    () => {
                        $wire.$call('storeTinyMceImage')
                            .then((url) => {
                                if (!url) {
                                    throw new Error('圖片上傳失敗');
                                }
                                resolve(url);
                            })
                            .catch((err) => {
                                const message = err?.message || '上傳失敗，請稍後再試';
                                notifyError(message);
                                reject({
                                    message,
                                    remove: true
                                });
                            });
                    },
                    () => {
                        const message = '上傳失敗，請稍後再試';
                        notifyError(message);
                        reject({
                            message,
                            remove: true
                        });
                    },
                    (event) => {
                        if (typeof progress === 'function' && event?.detail?.progress !== undefined) {
                            progress(event.detail.progress);
                        }
                    }
                );
            });

            // Mobile 版面修正（Flux grid 會保留 sidebar 欄位，導致主內容變窄）
            const setupMobileLayoutFix = () => {
                if (!document.querySelector('[data-flux-main]')) return null;

                const styleId = 'gd-flux-mobile-fix-style';
                if (!document.getElementById(styleId)) {
                    const style = document.createElement('style');
                    style.id = styleId;
                    style.textContent = `
                        @media (max-width: 1023px) {
                            body.gd-flux-mobile-fix {
                                grid-template-columns: 0 minmax(0, 1fr) 0 !important;
                            }
                        }
                    `;
                    document.head.appendChild(style);
                }

                const store = window.__gdTinymceMobileFix ?? {
                    count: 0,
                    media: null,
                    handler: null,
                };

                store.count += 1;

                if (!store.media) {
                    store.media = window.matchMedia('(max-width: 1023px)');
                    store.handler = () => {
                        document.body.classList.toggle('gd-flux-mobile-fix', store.media.matches);
                    };
                    store.media.addEventListener('change', store.handler);
                    store.handler();
                }

                window.__gdTinymceMobileFix = store;

                return () => {
                    const current = window.__gdTinymceMobileFix;
                    if (!current) return;

                    current.count = Math.max(0, current.count - 1);

                    if (current.count === 0) {
                        current.media?.removeEventListener('change', current.handler);
                        document.body.classList.remove('gd-flux-mobile-fix');
                        document.getElementById(styleId)?.remove();
                        window.__gdTinymceMobileFix = null;
                    }
                };
            };

            const cleanupMobileFix = setupMobileLayoutFix();
            if (cleanupMobileFix) {
                addCleanup(cleanupMobileFix);
            }

            // TinyMCE 初始化設定
            tinymce.init({
                target: textarea,
                language: 'zh_TW', // 設定語言為繁體中文
                base_url: '/js/tinymce', // TinyMCE 的基礎路徑
                suffix: '.min', // 使用壓縮版本的檔案
                height: 900, // 編輯器高度
                menubar: false, // 隱藏主選單
                quickbars_insert_toolbar: false, // 關閉快速工具列
                branding: false, // 隱藏 TinyMCE 的品牌標誌
                resize: true, // 允許調整編輯器大小
                license_key: 'gpl', // 設定授權類型
                promotion: false, // 關閉推廣訊息
                toolbar_sticky: true, // 工具列固定
                toolbar_mode: 'sliding', // 工具列模式為滑動
                // 依站台主題切換皮膚與內容樣式（避免亮/暗色衝突）
                skin: localStorage.getItem('flux.appearance') === 'light' ? 'oxide' : 'oxide-dark',
                content_css: localStorage.getItem('flux.appearance') === 'light' ? 'default' : 'dark',
                font_size_formats: '12px 14px 16px 18px 20px 24px 28px 32px 36px 40px 44px 48px 52px 56px 60px 64px 68px 72px 80px 88px 96px 104px 112px 120px 128px 136px 144px 152px 160px 176px 192px 200px', // 字體大小選項
                font_family_formats: '思源黑體=Noto Sans TC;思源宋體=Noto Serif TC;Roboto=roboto;Arial=arial,helvetica,sans-serif;Courier New=courier new,courier,monospace;AkrutiKndPadmini=Akpdmi-n', // 字體選項
                plugins: 'preview importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media codesample table charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount help charmap quickbars emoticons code', // 啟用的插件
                toolbar: "undo redo | accordion accordionremove | blocks fontfamily fontsize | bold italic underline strikethrough | align numlist bullist | link image | table media | lineheight outdent indent| forecolor backcolor removeformat | charmap emoticons | fullscreen preview | pagebreak anchor codesample | ltr rtl",
                relative_urls: false, // 使用絕對路徑
                automatic_uploads: true, // 自動上傳圖片
                images_upload_handler: ImagePost, // 自訂圖片上傳處理函式
                setup: (editor) => {
                    editorInstance = editor;

                    // 初始化時，把 Livewire 內容塞進編輯器。
                    editor.on('init', () => {
                        const initialValue = $wire.$get(model);
                        if (initialValue != null) {
                            editor.setContent(initialValue);
                            lastValue = initialValue ?? '';
                            lastSentValue = initialValue ?? '';
                        }
                    });

                    // live：輸入時即更新；lazy/defer：change/blur 時更新
                    if (isLive) {
                        const schedule = () => {
                            const content = editor.getContent();
                            setLocal(content);
                            queueNetwork(content);
                        };
                        editor.on('input', schedule);
                        editor.on('change', schedule);
                    } else {
                        editor.on('change', () => {
                            const content = editor.getContent();
                            setLocal(content);
                            commitNetwork(content);
                        });
                    }

                    // 失焦時：確保內容同步到 Livewire 模型
                    editor.on('blur', () => flushSync({
                        commit: true
                    }));

                    // Livewire 模型改變時：更新編輯器內容（避免外部更新不同步）
                    if (typeof $wire.$watch === 'function') {
                        const unwatch = $wire.$watch(model, (newValue) => {
                            if (newValue !== editor.getContent()) {
                                editor.resetContent(newValue || '');
                                lastValue = newValue ?? '';
                                lastSentValue = newValue ?? '';
                                putCursorToEnd(editor);
                            }
                        });

                        if (typeof unwatch === 'function') {
                            addCleanup(unwatch);
                        }
                    }
                },
            });

            bindFormSubmit();

            // DOM 被移除時要清掉事件、計時器與 TinyMCE 實例。
            const observer = new MutationObserver(() => {
                if (!document.body.contains(textarea)) {
                    cleanupTasks.forEach((cleanup) => cleanup());
                    cleanupTasks.length = 0;

                    if (editorInstance) {
                        editorInstance.remove();
                        editorInstance = null;
                    } else if (window.tinymce) {
                        tinymce.remove(textarea);
                    }

                    textarea.dataset.tinymceInitialized = '0';
                    observer.disconnect();
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            addCleanup(() => observer.disconnect());

            // 將游標移到內容末尾，避免跳回開頭
            function putCursorToEnd(editor) {
                editor.selection.select(editor.getBody(), true);
                editor.selection.collapse(false);
            }
        }
    </script>
@endscript
