<?php

namespace App\Livewire\Backend\Traits;

use Illuminate\Database\Eloquent\Model;

trait SetupTrait
{
    // 當前操作的 Model 類別
    public string $traitModel;

    // 可用的語系列表
    public $traitLocales = [];

    // 頁面標題
    public $traitPageTitle = '';

    // 設定 Model、標題、語系
    public function setupTrait(Model $currentModel, ?string $pageTitle = '', ?string $parentTitle = '', array $locales = [])
    {
        // 組合頁面標題
        $this->traitPageTitle = ! empty($parentTitle)
            ? $parentTitle.' - '.$pageTitle
            : $pageTitle;

        // 設定 Model 類別
        $this->traitModel = $currentModel::class;

        // 設定語系列表
        $this->traitLocales = $locales;
    }
}
