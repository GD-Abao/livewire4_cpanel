<?php

namespace App\Livewire\Backend\Traits;

use Flux\Flux;

trait SingleActionTrait
{
    // 目前選取的資料 ID
    public $traitSelectedId = null;

    // 目前選取的排序數字
    public $traitSelectedSortNumber = null;

    // 單筆啟用/停用切換
    public function toggleActiveTrait($id, $column = 'is_active')
    {
        $model = $this->traitModel::query()->find($id);

        if ($model && $model->isFillable($column)) {
            $model->update([
                $column => ! $model->$column,
            ]);
        }
    }

    // 開啟排序視窗並載入資料
    public function openSortNumberModalTrait($id)
    {
        $this->traitSelectedId = $id;
        $model = $this->traitModel::query()->find($id);
        $this->traitSelectedSortNumber = $model?->sort_number;
        Flux::modal('openSortNumberModal')->show();
    }

    // 儲存排序數字
    public function saveSortNumberTrait($sortNumberColumn = 'sort_number')
    {
        $this->validate([
            'traitSelectedSortNumber' => 'required|integer|min:1',
        ], [
            'traitSelectedSortNumber.required' => '排序數字必填',
            'traitSelectedSortNumber.integer' => '排序數字必須是整數',
            'traitSelectedSortNumber.min' => '排序數字必須大於 0',
        ]);

        $model = $this->traitModel::query()->find($this->traitSelectedId);

        if ($model && $model->isFillable($sortNumberColumn)) {
            $model->update([
                $sortNumberColumn => $this->traitSelectedSortNumber,
            ]);
        }

        Flux::modal('openSortNumberModal')->close();
    }
}
