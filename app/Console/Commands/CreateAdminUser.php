<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
                            {--name= : 管理員名稱 / Admin name}
                            {--email= : 管理員 Email / Admin email}
                            {--password= : 管理員密碼 / Admin password}
                            {--verified : 標記為已驗證 / Mark email as verified}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '建立或更新管理員（預設互動式） / Create or update admin';

    protected function configure(): void
    {
        parent::configure();

        $this->setHelp(<<<'HELP'
範例 / Example:
  php artisan admin:create --name="Admin" --email="admin@example.com" --password="StrongPass123" --verified
  php artisan admin:create
HELP
        );
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('姓名', 'Admin');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('密碼（留空則自動產生）');

        if ($password === null || $password === '') {
            $password = Str::random(16);
            $this->warn("已產生密碼: {$password}");
        }

        $attributes = [
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ];

        if ($this->option('verified') && Schema::hasColumn('users', 'email_verified_at')) {
            $attributes['email_verified_at'] = now();
        }

        if (Schema::hasColumn('users', 'is_admin')) {
            $attributes['is_admin'] = 1;
        }

        if (Schema::hasColumn('users', 'role')) {
            $attributes['role'] = 'admin';
        }

        $user = User::updateOrCreate(['email' => $email], $attributes);

        $this->info("管理員已建立/更新: {$user->email}");

        return self::SUCCESS;
    }
}
