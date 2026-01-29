<?php

use App\Livewire\GdActions\Logout;
use Illuminate\Support\Facades\Route;

// 白名單
$allowedEmails = ['admin@example.com', 'test@example.com'];

Gate::define('gd-admin-whitelist', function ($user) use ($allowedEmails) {
    return in_array($user->email, $allowedEmails, true);
});

$gdAdminLocales = [
    'zh_TW' => '中文',
    'en_US' => 'English',
];

// 後台選單
$adminMenu = [
    [
        'route' => 'dashboard',
        'url' => 'dashboard',
        'page' => 'backend.dashboard',
        'title' => 'Home',
        'icon' => 'academic-cap',
        'show' => true,
        'expanded' => true,
        'children' => [],
    ],
    [
        'title' => '最新消息',
        'show' => true,
        'expanded' => true,
        'children' => [
            [
                'title' => '列表',
                'route' => 'news.index',
                'url' => 'news',
                'page' => 'backend.news.index',
                'show' => true,
                'locales' => $gdAdminLocales,
            ],
            [
                'title' => '新增',
                'route' => 'news.edit',
                'url' => 'news/edit/{id?}',
                'page' => 'backend.news.edit',
                'show' => false,
                'locales' => $gdAdminLocales,
            ],
        ],
    ],
];

App::singleton('gdAdminMenu', fn () => $adminMenu);

// 註冊後台選單路由
function registerGdAdminMenuRoutes(array $menus, ?string $parentTitle = null): void
{
    foreach ($menus as $menu) {
        if (! empty($menu['url']) && ! empty($menu['page']) && ! empty($menu['route'])) {
            Route::livewire($menu['url'], 'pages::'.$menu['page'])
                ->name($menu['route'])
                ->defaults('pageTitle', $menu['title'] ?? null)
                ->defaults('parentTitle', $parentTitle)
                ->defaults('locales', $menu['locales'] ?? []);
        }

        if (! empty($menu['children']) && is_array($menu['children'])) {
            registerGdAdminMenuRoutes($menu['children'], $menu['title'] ?? $parentTitle);
        }
    }
}

Route::prefix('gd-admin')->name('gd-admin.')->group(function () {
    // 登入頁面
    Route::get('/login', function () {
        session()->flash('url.intended', route('gd-admin.dashboard'));

        return view('livewire.gd-admin-auth.login');
    })->name('login');

    // 登入後頁面
    Route::middleware(['auth', 'verified', 'can:gd-admin-whitelist'])->group(function () {
        Route::redirect('/', '/gd-admin/dashboard');
        Route::post('/logout', Logout::class)->name('logout');
        registerGdAdminMenuRoutes(app('gdAdminMenu'));
    });
});
