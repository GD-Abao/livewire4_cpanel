<?php

namespace App\Livewire\Backend\Traits;

use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Url;

trait FilterableTrait
{
    // 語系（對應 URL ?locale=）
    #[Url(as: 'locale')]
    public string $traitLocale = 'All';

    // 關鍵字搜尋（對應 URL ?search=）
    #[Url('search')]
    public string $traitSearch = '';

    // 建立日期區間（對應 URL ?start_date= ?end_date=）
    #[Url('start_date')]
    public ?string $traitStartDate = null;

    #[Url('end_date')]
    public ?string $traitEndDate = null;

    // 啟用狀態（對應 URL ?is_active=）
    #[Url('is_active')]
    public ?string $traitIsActive = null;

    // 日期排序（對應 URL ?sort_by_date=）
    #[Url('sort_by_date', except: 'desc')]
    public string $traitSortByDate = 'desc';

    // 驗證日期區間
    public function validateDateRange()
    {
        if (! $this->traitStartDate || ! $this->traitEndDate) {
            return;
        }

        $this->validate([
            'traitEndDate' => 'date|after_or_equal:traitStartDate',
        ], [
            'traitEndDate.after_or_equal' => '結束日期必須在開始日期之後',
        ]);
    }

    // 套用篩選並關閉 Modal
    public function goFilterTrait()
    {
        $this->validateDateRange();
        Flux::modal('searchAndFilter')->close();
    }

    // 重置篩選並關閉 Modal
    public function resetFilterTrait()
    {
        $this->reset(
            'traitSearch',
            'traitStartDate',
            'traitEndDate',
            'traitIsActive',
        );
        $this->resetPage();
        Flux::modal('searchAndFilter')->close();
    }

    // 建立共用的查詢（搜尋、日期、狀態、語系、排序）
    protected function createBaseQuery(array $withModels = [], $searchColumns = ['title'], $isActiveColumn = 'is_active'): Builder
    {
        $query = $this->traitModel::query()
            ->with($withModels)
            ->when($this->traitSearch, function ($query) use ($searchColumns) {
                $query->where(function ($subQuery) use ($searchColumns) {
                    foreach ($searchColumns as $column) {
                        $subQuery->orWhere($column, 'like', '%'.$this->traitSearch.'%');
                    }
                });
            })
            ->when($this->traitStartDate, function ($query) {
                $query->whereDate('created_at', '>=', $this->traitStartDate);
            })
            ->when($this->traitEndDate, function ($query) {
                $query->whereDate('created_at', '<=', $this->traitEndDate);
            })
            ->when($this->traitIsActive !== null && $this->traitIsActive !== '', function ($query) use ($isActiveColumn) {
                $query->where($isActiveColumn, (int) $this->traitIsActive);
            })
            ->when($this->traitLocale !== 'All', function ($query) {
                $query->where('locale', $this->traitLocale);
            });

        $table = $this->traitModel::query()->getModel()->getTable();
        if (Schema::hasColumn($table, 'sort_number')) {
            $query->orderByDesc('sort_number');
        }

        $query->orderBy('created_at', $this->traitSortByDate);

        return $query;
    }
}
