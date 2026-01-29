<?php

use App\Livewire\GdAdminSettings\Appearance;
use App\Livewire\GdAdminSettings\Password;
use App\Livewire\GdAdminSettings\Profile;
use App\Livewire\GdAdminSettings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::prefix('gd-admin')->name('gd-admin.')->group(function () {
    Route::middleware(['auth'])->group(function () {
        Route::redirect('settings', 'gd-admin/settings/profile');

        Route::livewire('settings/profile', Profile::class)->name('settings.profile');
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::livewire('settings/password', Password::class)->name('settings.password');
        Route::livewire('settings/appearance', Appearance::class)->name('settings.appearance');

        Route::livewire('settings/two-factor', TwoFactor::class)
            ->middleware(
                when(
                    Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                    ['password.confirm'],
                    [],
                ),
            )
            ->name('settings.two-factor');
    });
});
