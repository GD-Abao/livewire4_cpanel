<?php

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithFileUploads;

// Livewire 元件：圖片上傳、驗證、排序與移除。
new class extends Component {
    use WithFileUploads;

    // 外部可雙向綁定的圖片清單（路徑或 URL）。
    #[Modelable]
    public array $images = [];

    // Livewire 暫存的上傳檔案。
    public $tempImage;

    // 單張最大檔案大小（KB）。
    public int $maxFileSize = 3072;

    // 最多允許上傳的張數。
    public int $maxImages = 1;

    // DOM root id，供前端 JS 綁定與事件掛載。
    public string $componentId;

    // 由父層傳入的錯誤訊息（需即時反應）。
    #[Reactive]
    public ?string $errorMessage = null;

    public function mount(): void
    {
        // 若父層未指定 id，使用隨機值避免衝突。
        $this->componentId = $this->componentId ?? 'upload-' . Str::random(10);
        // 避免空值或非字串混入圖片清單。
        $this->images = array_values(array_filter((array) $this->images, fn($img) => is_string($img) && $img !== ''));
    }

    protected function validateTempImage(): void
    {
        // 只允許圖片，並限制大小。
        $this->validate(
            [
                'tempImage' => ['image', "max:{$this->maxFileSize}"],
            ],
            [
                'tempImage.image' => '檔案必須是圖片格式',
                'tempImage.max' => '圖片大小不可超過 ' . $this->maxFileSize / 1024 . 'MB',
            ],
        );
    }

    public function updatedTempImage(): void
    {
        if (!$this->tempImage) {
            return;
        }

        try {
            // 驗證通過後才寫入儲存空間。
            $this->validateTempImage();

            $path = $this->tempImage->store('images/' . now()->format('Ymd'), 'public');
            $this->images = is_array($this->images) ? $this->images : [];

            // 單張上傳：直接覆蓋；多張上傳：尚有空位才加入。
            if ($this->maxImages <= 1) {
                $this->images = [$path];
            } elseif (count($this->images) < $this->maxImages) {
                $this->images[] = $path;
            }

            $this->tempImage = null;
        } catch (ValidationException $e) {
            $this->tempImage = null;
            throw $e;
        }
    }

    public function removeImage(int $index): void
    {
        // 移除前先清理不合法值，避免索引錯亂。
        $this->images = is_array($this->images) ? array_filter($this->images, fn($img) => is_string($img) && $img !== '') : [];

        if (isset($this->images[$index])) {
            unset($this->images[$index]);
            $this->images = array_values($this->images);
        }
    }

    public function removeTempImage(): void
    {
        // 取消暫存上傳並清除驗證錯誤。
        $this->tempImage = null;
        $this->resetErrorBag('tempImage');
    }

    public function updateImageOrder(int $from, int $to): void
    {
        // 依拖曳索引調整排序。
        $this->images = is_array($this->images) ? array_values($this->images) : [];

        if (!isset($this->images[$from]) || $to < 0 || $to >= count($this->images)) {
            return;
        }

        $image = array_splice($this->images, $from, 1);
        array_splice($this->images, $to, 0, $image);
    }
};
?>

@php
    $visibleImages = array_values(array_filter((array) $images, fn($img) => !empty($img) && is_string($img)));
    $uploadedImageCount = count($visibleImages);
    $hasPendingImage = $tempImage ? 1 : 0;
    $canShowUploadButton = $uploadedImageCount + $hasPendingImage < $maxImages;
@endphp

<div id="{{ $componentId }}" data-upload-root>
    <p class="text-xs opacity-50 py-2">
        <!-- 提示：限制大小與主圖規則 -->
        圖片大小上限：{{ $maxFileSize / 1024 }}MB{{ $maxImages > 1 ? '，第一張圖片為主圖' : '' }}
    </p>

    <div class="w-5xl overflow-x-auto max-w-full">
        <div class="flex flex-nowrap gap-4 min-w-max">
            @if ($canShowUploadButton)
                <div class="shrink-0">
                    <!-- 拖放/點擊上傳區 -->
                    <label
                        class="grid place-items-center w-36 h-36 border-2 border-dotted rounded cursor-pointer hover:bg-zinc-200 dark:hover:bg-zinc-900 transition relative"
                        data-upload-dropzone>
                        <span class="text-3xl text-gray-400">+</span>

                        <input type="file" class="hidden" data-upload-input wire:model="tempImage" accept="image/*">

                        <div wire:loading wire:target="tempImage"
                            class="absolute bottom-2 left-1/2 -translate-x-1/2 px-2 rounded text-xs">
                            上傳中...
                        </div>
                    </label>
                </div>
            @endif

            <div class="flex flex-nowrap gap-4" data-upload-list>
                @foreach ($visibleImages as $index => $img)
                    <!-- 已上傳的圖片 -->
                    <div class="relative size-36 shrink-0" data-image-index="{{ $index }}" draggable="true"
                        wire:key="upload-image-{{ $componentId }}-{{ $index }}">
                        <img src="{{ Str::startsWith($img, ['http://', 'https://']) ? $img : Storage::url($img) }}"
                            class="object-cover w-full h-full rounded border aspect-square" alt="">

                        <button type="button" wire:click="removeImage({{ $index }})"
                            class="absolute top-1 right-1 bg-red-700 text-white rounded-full w-6 h-6 flex items-center justify-center z-20">
                            &times;
                        </button>
                    </div>
                @endforeach

                @if ($tempImage)
                    <!-- 尚未正式儲存的暫存圖片 -->
                    <div class="relative w-36 h-36 shrink-0" wire:key="upload-temp-{{ $componentId }}">
                        <img src="{{ $tempImage->temporaryUrl() }}"
                            class="object-cover w-full h-full rounded border aspect-square" alt="">

                        <button type="button" wire:click="removeTempImage"
                            class="absolute top-1 right-1 bg-red-700 text-white rounded-full w-6 h-6 flex items-center justify-center z-20">
                            &times;
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @error('tempImage')
        <!-- 上傳驗證錯誤（Livewire） -->
        @php
            // 超過 PHP/Livewire 上傳限制時，會回傳「上傳失敗」而不是 max 訊息。
            $displayMessage = $message;
            if (str_contains($message, '上傳失敗') || str_contains($message, 'failed to upload')) {
                $displayMessage = '圖片大小不可超過 '.($maxFileSize / 1024).'MB';
            }
        @endphp
        <span class="text-red-400 text-sm inline-flex items-center mt-2 gap-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            {{ $displayMessage }}
        </span>
    @enderror

    @if ($errorMessage)
        <!-- 來自父層的錯誤訊息 -->
        <span class="text-red-400 text-sm inline-flex items-center mt-2 gap-x-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            {{ $errorMessage }}
        </span>
    @endif
</div>

@script
    <script>
        const componentId = @js($componentId);
        const root = document.getElementById(componentId);

        if (root && root.dataset.uploadInitialized !== '1') {
            root.dataset.uploadInitialized = '1';

            const dropzone = root.querySelector('[data-upload-dropzone]');
            const input = root.querySelector('[data-upload-input]');
            const list = root.querySelector('[data-upload-list]');
            let dragIndex = null;

            if (dropzone && input) {
                const setDragging = (state) => {
                    // 拖曳時加底色提示。
                    dropzone.classList.toggle('bg-teal-900', state);
                };

                // 拖曳檔案進入上傳區。
                dropzone.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    setDragging(true);
                });

                dropzone.addEventListener('dragleave', (event) => {
                    event.preventDefault();
                    setDragging(false);
                });

                dropzone.addEventListener('drop', (event) => {
                    event.preventDefault();
                    setDragging(false);

                    if (!event.dataTransfer?.files?.length) {
                        return;
                    }

                    // 交由 input 觸發 Livewire 上傳流程。
                    input.files = event.dataTransfer.files;
                    input.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                });
            }

            if (list) {
                // 拖曳排序：記錄起點索引。
                list.addEventListener('dragstart', (event) => {
                    const item = event.target.closest('[data-image-index]');
                    if (!item) return;

                    dragIndex = Number(item.dataset.imageIndex);
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', '');
                });

                // 拖曳經過時顯示高亮。
                list.addEventListener('dragover', (event) => {
                    const item = event.target.closest('[data-image-index]');
                    if (!item) return;

                    event.preventDefault();
                    item.classList.add('ring-2', 'ring-teal-500');
                });

                // 離開時移除高亮。
                list.addEventListener('dragleave', (event) => {
                    const item = event.target.closest('[data-image-index]');
                    if (!item) return;

                    item.classList.remove('ring-2', 'ring-teal-500');
                });

                // 放下時送出排序更新。
                list.addEventListener('drop', (event) => {
                    const item = event.target.closest('[data-image-index]');
                    if (dragIndex === null || !item) return;

                    event.preventDefault();
                    item.classList.remove('ring-2', 'ring-teal-500');

                    const dropIndex = Number(item.dataset.imageIndex);
                    if (!Number.isNaN(dropIndex) && dropIndex !== dragIndex) {
                        $wire.updateImageOrder(dragIndex, dropIndex);
                    }
                    dragIndex = null;
                });

                // 拖曳結束：清除狀態與高亮。
                list.addEventListener('dragend', () => {
                    dragIndex = null;
                    list.querySelectorAll('[data-image-index]').forEach((item) => {
                        item.classList.remove('ring-2', 'ring-teal-500');
                    });
                });
            }
        }
    </script>
@endscript
