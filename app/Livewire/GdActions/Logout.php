<?php

namespace App\Livewire\GdActions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * 後台登出
     * Log the current user out and redirect to gd-admin login.
     */
    public function __invoke()
    {
        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        // 登出後導向後台登入頁
        return redirect()->route('gd-admin.login');
    }
}
