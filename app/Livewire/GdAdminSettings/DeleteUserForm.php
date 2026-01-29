<?php

namespace App\Livewire\GdAdminSettings;

use App\Concerns\PasswordValidationRules;
use App\Livewire\GdActions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DeleteUserForm extends Component
{
    use PasswordValidationRules;

    // 指定使用 gd-admin-settings 的 view
    protected static string $view = 'livewire.gd-admin-settings.delete-user-form';

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        // 刪除帳號後導向後台登入頁
        $this->redirect(route('gd-admin.login'), navigate: true);
    }
}
