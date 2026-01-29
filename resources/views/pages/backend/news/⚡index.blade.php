<?php

use App\Livewire\Backend\Traits\BulkActionsTrait;
use App\Livewire\Backend\Traits\FilterableTrait;
use App\Livewire\Backend\Traits\SetupTrait;
use App\Livewire\Backend\Traits\SingleActionTrait;
use App\Models\News;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.gd-admin')] class extends Component
{
    use BulkActionsTrait, FilterableTrait, SetupTrait, SingleActionTrait, WithPagination;

    // 每頁顯示數量
    public int $perPage = 6;

    // 初始化頁面：注入 Model、標題、語系清單
    public function mount($pageTitle = null, $parentTitle = null, $locales = [])
    {
        $this->setupTrait(currentModel: new News, pageTitle: $pageTitle, parentTitle: $parentTitle, locales: $locales);
    }

    // 統一查詢入口：使用 FilterableTrait 的條件並可在此擴充
    protected function bulkBaseQuery(): Builder
    {
        $query = $this->createBaseQuery(searchColumns: ['title']);

        // 關係模組（範例）
        // 單一關聯篩選
        // if (!empty($this->tagId)) {
        //     $query->whereHas('tags', fn ($q) => $q->whereKey($this->tagId));
        // }
        //
        // 多個關聯篩選
        // if (!empty($this->tagIds)) {
        //     $query->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $this->tagIds));
        // }

        // 需要擴充條件時，在這裡追加 where/whereHas

        return $query;
    }

    /*
    // 備註：若要在「批次刪除」時清掉關聯資料，可在這裡實作
    // 例如：先刪 news_images，再刪 news（只刪資料庫，不刪檔案）
    protected function handleBulkDelete(Builder $query, array $ids): void
    {
        $models = $query->get();

        foreach ($models as $model) {
            $model->newsImages()->delete();
            $model->delete();
        }
    }
    */

    // BulkActionsTrait 需要：回傳「當前頁」的 ID 列表
    public function getPageIds(): array
    {
        return $this->bulkBaseQuery()->paginate($this->perPage)->getCollection()->pluck('id')->toArray();
    }

    // 取得列表資料（分頁或完整）
    public function getResult($paginate = true)
    {
        $query = $this->bulkBaseQuery();

        return $paginate ? $query->paginate($this->perPage) : $query->get();
    }

    // 提供 Blade 使用的分頁資料
    #[Computed]
    public function items()
    {
        return $this->getResult(true);
    }
};
?>

<div>
    {{-- 資料名稱 --}}
    @php($items = $this->items)
    {{-- 批量跟選取功能 --}}
    @include('livewire.backend.share.bulk-actions')
    {{-- 篩選跟搜尋功能 --}}
    @include('livewire.backend.share.filterable')


    {{-- 主列表 --}}
    @php($currentPage = $items->currentPage())

    <main class="2xl:max-w-5xl container">
        <!--新增按鈕-->
        <flux:button wire:show="true" href="{{ route('gd-admin.news.edit') . '?locale=' . $traitLocale }}"
            icon:trailing="plus">
            新增
        </flux:button>

        <!-- 列表開始-自訂區 -->
        <ul class="space-y-4 mt-4">
            @forelse ($items as $item)
                <li class="dark:bg-zinc-900 bg-zinc-100 w-full p-4 rounded-xl" wire:key="list-{{ $item->id }}">
                    <article class="lg:flex space-y-4 lg:space-y-0 justify-between gap-x-0 lg:gap-x-4 items-center">

                        <!-- 圖片區 -->
                        <figure wire:show="true">
                            @if (isset($item->image_url))
                                <img src="{{ Str::startsWith($item->image_url, ['http://', 'https://']) ? $item->image_url : Storage::url($item->image_url) }}"
                                    class="size-12 rounded-full object-cover" alt="">
                            @else
                                <flux:avatar circle class="size-12" initials="無" />
                            @endif
                        </figure>


                        <!--語系標籤-->
                        <flux:badge variant="color" color="red" class="text-xs">{{ $item->locale }}</flux:badge>

                        <!-- 內容區 -->
                        <section wire:show="true" class="flex-1 break-all">
                            <p class="text-xs opacity-50">建立日期：{{ $item->created_at->format('Y.m.d') }}</p>
                            <p>
                                {{ $item->title }}
                            </p>
                        </section>

                        <!-- 選單區 -->
                        <flux:button.group class="place-self-center md:place-self-end lg:place-self-auto">
                            <!-- 選取框 -->
                            <flux:button wire:show="true">
                                <flux:checkbox wire:model.live="traitSelected" value="{{ $item->id }}"
                                    label="選取" />
                            </flux:button>

                            <!-- 基本功能鈕，排序、啟用 -->
                            <!-- 上架狀態 -->
                            <flux:button wire:show="true">
                                <flux:tooltip content="{{ $item->is_active ? '關閉' : '啟用' }}">
                                    <flux:switch :checked="(bool) $item->is_active"
                                        wire:click="toggleActiveTrait({{ $item->id }})" />
                                </flux:tooltip>
                            </flux:button>

                            <!-- 排序按鈕 -->
                            <flux:tooltip wire:show="true" content="排序">
                                <flux:button class="min-w-24" icon="numbered-list"
                                    wire:click="openSortNumberModalTrait({{ $item->id }})">
                                    {{ $item->sort_number }}
                                </flux:button>
                            </flux:tooltip>

                            <!-- 編輯選單 -->
                            <flux:dropdown position="bottom" align="end">
                                <flux:button icon="ellipsis-horizontal" />
                                <flux:navmenu>
                                    <flux:navmenu.item
                                        href="{{ route('gd-admin.news.edit', array_filter(['id' => $item->id, 'locale' => $item->locale, 'page' => $currentPage], fn($value) => $value !== null && $value !== '')) }}"
                                        icon="pencil-square">
                                        編輯
                                    </flux:navmenu.item>
                                </flux:navmenu>
                            </flux:dropdown>
                        </flux:button.group>

                    </article>
                </li>
            @empty
                <div class="text-center">😉沒有項目</div>
            @endforelse
        </ul>
        <!-- 列表結束-自訂區 -->

        {{-- 分頁 --}}
        <div class="pb-20">
            <x-gd-admin.gd-admin-pagination :paginator="$this->items" />
        </div>
    </main>

    {{-- 動態視窗，基本有放排序的修改，如要添加在增加於此 --}}
    @include('livewire.backend.share.modals')
</div>