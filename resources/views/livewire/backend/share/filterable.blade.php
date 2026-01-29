<div class="fixed 2xl:top-8 bottom-0 right-8 z-10 space-y-2">
    {{-- 篩選條件顯示位置 --}}
    <section class="mb-4">
        @if($traitSearch or $traitStartDate or $traitEndDate)
        <flux:modal.trigger name="searchAndFilter">
            <flux:badge as="button" icon="adjustments-horizontal" class="w-full">
                <div class="gird place-items-start">
                    @if($traitSearch)
                    <p>搜尋:{{ $traitSearch }}</p>
                    @endif
                    @if($traitStartDate)
                    <p>開始:{{ $traitStartDate }}</p>
                    @endif
                    @if($traitEndDate)
                    <p>結束:{{ $traitEndDate }}</p>
                    @endif
                </div>
            </flux:badge>
        </flux:modal.trigger>
        @endif
    </section>

    {{-- 篩選按鈕列表 --}}
    <flux:button.group>
        <flux:modal.trigger name="searchAndFilter">
            <flux:button icon="magnifying-glass">搜尋、篩選</flux:button>
        </flux:modal.trigger>



        <flux:dropdown>
            <flux:button icon="numbered-list" icon:trailing="chevron-down">
                {{ $traitSortByDate == 'desc' ? '新日期' : '舊日期' }}
            </flux:button>

            <flux:menu>
                <flux:menu.radio.group wire:model.live="traitSortByDate">
                    <flux:menu.radio value="desc">新日期</flux:menu.radio>
                    <flux:menu.radio value="asc">舊日期</flux:menu.radio>
                </flux:menu.radio.group>
            </flux:menu>
        </flux:dropdown>
    </flux:button.group>

    <flux:button.group>
        {{-- 啟用狀態下拉選單 --}}
        <flux:dropdown align="center">
            <flux:button class="w-full" icon:trailing="chevron-down">
                狀態:
                @if(is_null($traitIsActive) || $traitIsActive === '')
                全部
                @elseif($traitIsActive == 1 || $traitIsActive === true || $traitIsActive === '1')
                啟用
                @else
                未啟用
                @endif
            </flux:button>

            <flux:menu>
                <flux:menu.radio.group wire:model.live="traitIsActive">
                    <flux:menu.radio value="" checked>全部</flux:menu.radio>
                    <flux:menu.radio value="1">啟用</flux:menu.radio>
                    <flux:menu.radio value="0">未啟用</flux:menu.radio>
                </flux:menu.radio.group>
            </flux:menu>
        </flux:dropdown>
        <!-- 語言選擇 -->
        <flux:dropdown>
            <flux:button icon:trailing="chevron-down">語系:{{ $traitLocale }}</flux:button>
            <flux:menu>
                <flux:menu.radio.group wire:model.live="traitLocale">
                    <flux:menu.radio value="All" checked>全部</flux:menu.radio>
                    @foreach ($traitLocales as $key => $localeItem)
                    <flux:menu.radio value="{{ $key }}">{{ $localeItem }}</flux:menu.radio>
                    @endforeach
                </flux:menu.radio.group>
            </flux:menu>
        </flux:dropdown>
    </flux:button.group>

    {{-- 搜尋與篩選Modal --}}
    <flux:modal name="searchAndFilter" class="md:w-96">
        <form wire:submit='goFilterTrait' class="space-y-6">
            <div>
                <flux:heading>搜尋、篩選</flux:heading>
            </div>

            <flux:input wire:model="traitSearch" icon="magnifying-glass" placeholder="搜尋..." />
            <flux:input wire:model="traitStartDate" type="date" max="2999-12-31" label="開始日期" />
            <flux:input wire:model="traitEndDate" type="date" max="2999-12-31" label="結束日期" />

            <div class="flex items-center gap-x-2 mt-8">
                <flux:spacer />
                <flux:button type="button" wire:click='resetFilterTrait' variant="filled">重置所有條件</flux:button>
                <flux:button type="submit" variant="primary">保存篩選條件</flux:button>
            </div>
        </form>
    </flux:modal>
</div>