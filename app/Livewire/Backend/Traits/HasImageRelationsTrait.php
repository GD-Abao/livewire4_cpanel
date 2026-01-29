<?php

namespace App\Livewire\Backend\Traits;

trait HasImageRelationsTrait
{
    // 通用：同步圖片關聯（只處理資料庫，不刪檔案）
    protected function syncImageRelation($model, array $images, string $relation, string $imageColumn = 'image_url', string $sortColumn = 'sort_number'): void
    {
        if (! $model || ! method_exists($model, $relation)) {
            return;
        }

        $relationQuery = $model->$relation();
        $relationQuery->delete();

        $filtered = array_values(array_filter($images, fn ($img) => is_string($img) && $img !== ''));

        foreach ($filtered as $sortKey => $path) {
            $relationQuery->create([
                $imageColumn => $path,
                $sortColumn => $sortKey,
            ]);
        }
    }
}
