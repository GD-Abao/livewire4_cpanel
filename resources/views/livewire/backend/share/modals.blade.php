<!-- 排序修改 -->
<flux:modal name="openSortNumberModal" class="md:w-96">
    <form wire:submit="saveSortNumberTrait" class="space-y-6">
        <div>
            <flux:heading size="lg">排序</flux:heading>
        </div>
        <flux:input mask="99999" autofocus="autofocus" wire:model="traitSelectedSortNumber" />
        <flux:error name="traitSelectedSortNumber" />
        <div class="flex">
            <flux:spacer />
            <flux:button type="submit" variant="primary">保存</flux:button>
        </div>
    </form>
</flux:modal>
