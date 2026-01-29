<?php

namespace App\Livewire\GdAdminSettings;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.gd-admin')]
class Appearance extends Component
{
    // 指定使用 gd-admin-settings 的 view
    protected static string $view = 'livewire.gd-admin-settings.appearance';
}
