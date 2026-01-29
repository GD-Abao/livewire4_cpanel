<!-- 固定頂部區域：批量操作面板 -->
<section class="sticky top-0 z-10 xl:max-w-2xl 2xl:max-w-5xl w-full">
    {{-- 全選按鈕 --}}
    <section
        class="flex items-center justify-between backdrop-blur-md bg-zinc-100/50 dark:bg-zinc-900/50 rounded-2xl p-4 mb-4">
        {{-- 顯示頁面標題 --}}
        <h1 class="md:flex md:gap-x-2">
            {{-- 顯示頁面標題 --}}
            {{ $traitPageTitle }}
            <p class="text-xs opacity-50">排序數字由大到小，其次為創建日期</p>
        </h1>

        <flux:button>
            <flux:checkbox wire:model.live="traitSelectAll" label="全選" />
        </flux:button>
    </section>

    <!-- 選擇操作區：當有選中項目時顯示 -->
    @if (isset($traitSelected) and count($traitSelected) > 0)
    <section
        class="space-y-4 md:space-y-0 sm:flex items-center justify-between p-4 my-2 text-sm rounded-2xl sm:justify-start sm:space-x-6 dark:bg-black bg-zinc-100">
        {{-- 顯示已選項目數量 --}}
        <div>已選取：{{ count($traitSelected) }}筆</div>

        <!-- 根據選中數量顯示取消或全選按鈕 -->
        <section>
            @if (count($traitSelected) == $items->total())
            {{-- 取消全部選取 --}}
            <p wire:click='$set("traitSelected",[])' class="opacity-50">
                取消全部資料選取
            </p>
            @else
            {{-- 全選所有資料，包含分頁數據，點擊後觸發確認提示 --}}
            <flux:button wire:click='traitSelectAllItems' icon="light-bulb" wire:confirm="選擇全部資料做批量操作嗎？">
                共<span class="dark:text-amber-500 text-teal-500 px-2">{{ $items->total() }}</span>筆資料，全部都選取嗎?
            </flux:button>
            @endif
        </section>

        <!-- 批量操作下拉選單 -->
        <flux:dropdown>
            <flux:button icon:trailing="chevron-down">選擇操作</flux:button>
            <flux:menu>
                {{-- 啟用選中項目 --}}
                <flux:menu.item wire:click="bulkActionTrait('active')" icon="bookmark">啟用</flux:menu.item>
                {{-- 停用選中項目 --}}
                <flux:menu.item wire:click="bulkActionTrait('inactive')" icon="bookmark-slash">停用</flux:menu.item>
                {{-- 刪除選中項目，點擊後觸發確認提示 --}}
                <flux:menu.item wire:click="bulkActionTrait('delete')" wire:confirm='確認是否要刪除資料?' icon="trash"
                    variant="danger" kbd="⌫">
                    刪除
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </section>
    @endif
</section>