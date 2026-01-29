# GDCmsV5(Livewire_V4) 使用方法

---

## 安裝環境

```text
準備工具
composer
nodejs 下載LTS版本
WampServer 將以上工具安裝好，設定PHP環境變數 確認php版本 node.js composer
```

```bash
php -v
node -v
composer -v
成功會顯示版本號
PHP 8.2.0 (cli) (built: Aug  4 2020 15:02:26) ( NTS Visual C++ 2017 x64 )
v14.17.6
Composer version 2.1.6 2021-09-17 16:31:31
```

---

## 下載 laravel

```bash
<!-- 在電腦上全局安裝laravel -->
composer global require laravel/installer
<!-- 建立laravel專案 -->
laravel new example-app(專案名稱)
# 選擇 [livewire] Livewire
# 選擇 laravel
# 不選擇(no) Laravel Volt
# 選擇PHPUnit <- 就不會每次部屬GITHUB都要改測試套件
```

---

## 安裝 Laravel Lang(預設繁體中文)

```bash
composer require laravel-lang/common
php artisan lang:add zh_TW
php artisan lang:update
```

---

## 將 GD-CMS 下載的安裝包放入專案中

<span style="color: #d6336c; font-weight: bold; background: #fff3cd; padding: 4px 8px; border-radius: 4px; display: inline-block;">請務必將 GD-CMS 安裝包完整放入本專案資料夾，否則系統將無法正常運作！</span>

---

## 設定 `bootstrap/app.php`

加入 gd-admin 的重新導向

```php
use Illuminate\Http\Request;
```

找到 `->withMiddleware(function (Middleware $middleware): void {` 這段，改成：

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->redirectGuestsTo(function (Request $request) {
        if ($request->is('gd-admin/*') || $request->is('gd-admin')) {
            return route('gd-admin.login');
        }
        return route('login');
    });
})
```

## 建立後台管理員指令

註冊指令

```php
->withCommands([
    \App\Console\Commands\CreateAdminUser::class,
])
```

---

## 設定 routes/web.php

```php
// 在最後面，加上這一行 後台跟後台驗證登入
require __DIR__.'/gd-admin.php';
require __DIR__.'/gd-admin-settings.php';
```

---

## 設定.env，請連同.env.example 的內容一起修改

```env
# 專案名稱記得要設定成專案網站名稱
APP_NAME=GD-CMS

# 改語系
APP_LOCALE=zh_TW
APP_FALLBACK_LOCALE=zh_TW
APP_FAKER_LOCALE=zh_TW


# 有發信記得之後要設定Mail區域
# 千萬不可以把任何密碼放到.env.example裡面
```

---

## 設定時區

```php
// 在config/app.php裡面，時區改成台灣
'timezone' => 'Asia/Taipei',
```

## 註冊開放

<span style="color: #d6336c; font-weight: bold; background: #fff3cd; padding: 4px 8px; border-radius: 4px; display: inline-block;">
一般網站，都是不會給客戶註冊會員的功能，可以去 config\fortify.php，修改註冊功能
</span>

```php
  'features' => [
        // Features::registration(),  //這行註解掉
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
            // 'window' => 0
        ]),
    ],
```

## 未來不用 Seeder 建立管理員帳號，改用 Artisan 指令建立，前面已經有註冊過

```bash
php artisan admin:create
# 或
php artisan admin:create --name="Admin" --email="admin@example.com" --password="StrongPass123" --verified

# 刪除管理員
php artisan admin:remove --email="admin@example.com"
# 或指定 id
php artisan admin:remove --id=1 --force
```

---

## 使用 MYSQL MyISAM 引擎，手動匹配字串長度

路徑: app/Providers/AppServiceProvider.php

```php
use Illuminate\Support\Facades\Schema;
/**
 * Bootstrap any application services.
 */
public function boot(): void
{
    Schema::defaultStringLength(191);
}
```

---

## 資料庫遷移

```bash
php artisan migrate
```

---

## 開啟伺服器

```bash
<!-- 第一個終端開啟前端開發環境 -->
npm run dev
<!-- 第二個終端開啟php伺服器 -->
php artisan serve
```

## !!!重要，案件上線前

務必要記得設定好 admin 可登入的權限帳號，以及檢查 seeder 裡面有測試用的<test@example.com>沒有被設定到會員資料裡面去

## 修改 `routes\gd-admin.php`

```php
$allowedEmails = ['admin@example.com', 'test@example.com']; // 這裡可以添加允許登入後台的 email 列表
```

---

## livewire4 新版建立前後端指令

```bash
# 後台頁面
php artisan livewire:make pages::backend.程式名稱(news).頁面功能(index or edit)
# 例如: php artisan livewire:make pages::backend.news.index
```

```bash
# 前台頁面
php artisan livewire:make pages::frontend.程式名稱(news).頁面功能(index or show)
# 例如: php artisan livewire:make pages::frontend.news.index
```
---

如果要改回舊版 livewire3 的作法就在後墜加上 --class

```bash
php artisan livewire:make pages::backend.news.index --class
```