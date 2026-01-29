<?php

namespace App\Livewire\Backend\Traits;

trait BulkActionsTrait
{
    // 由使用者頁面提供的基礎查詢
    abstract protected function bulkBaseQuery();

    // 目前已選取的資料 ID
    public array $traitSelected = [];

    // 是否已勾選「全選」
    public bool $traitSelectAll = false;

    // 單筆選取變更時，同步「全選」狀態
    public function updatedTraitSelected()
    {
        $this->traitSelectAll = count($this->traitSelected) === count($this->getPageIds());
    }

    // 勾選「全選」時，改為選取當前頁所有 ID
    public function updatedTraitSelectAll()
    {
        $this->traitSelectAll
            ? $this->traitSelected = $this->getPageIds()
            : $this->traitSelected = [];
    }

    // 全選全部資料（不分頁）
    public function traitSelectAllItems()
    {
        $this->traitSelectAll = true;
        // 獲取所有（不分頁）結果並選取
        $this->traitSelected = $this->bulkBaseQuery()->pluck('id')->toArray();
    }

    // 批次操作：啟用/停用/刪除
    public function bulkActionTrait($action, $column = 'is_active')
    {
        match ($action) {
            'active' => $this->bulkBaseQuery()->whereIn('id', $this->traitSelected)->update([$column => true]),
            'inactive' => $this->bulkBaseQuery()->whereIn('id', $this->traitSelected)->update([$column => false]),
            'delete' => $this->bulkDeleteTrait(),
            default => throw new \InvalidArgumentException('無效的操作: '.$action),
        };

        session()->flash('gd-session-message', '狀態已更新');

        return redirect()->to(request()->header('Referer'));
    }

    // 批次刪除（預設直接刪除，可在個別頁面覆寫 handleBulkDelete）
    protected function bulkDeleteTrait(): void
    {
        if (empty($this->traitSelected)) {
            return;
        }

        $query = $this->bulkBaseQuery()->whereIn('id', $this->traitSelected);

        if (method_exists($this, 'handleBulkDelete')) {
            $this->handleBulkDelete($query, $this->traitSelected);
            return;
        }

        $query->delete();
    }
}
