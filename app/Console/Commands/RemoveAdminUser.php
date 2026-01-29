<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class RemoveAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:remove
                            {--email= : 管理員 Email / Admin email}
                            {--id= : 使用者 ID / User id}
                            {--force : 略過確認 / Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '刪除管理員（依 email 或 id） / Remove admin by email or id';

    protected function configure(): void
    {
        parent::configure();

        $this->setHelp(<<<'HELP'
範例 / Example:
  php artisan admin:remove --email="admin@example.com"
  php artisan admin:remove --id=1 --force
HELP
        );
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email');
        $id = $this->option('id');

        if (!$email && !$id) {
            $email = $this->ask('Email');
        }

        $query = User::query();
        if ($id) {
            $query->where('id', $id);
        } elseif ($email) {
            $query->where('email', $email);
        }

        $user = $query->first();
        if (!$user) {
            $this->warn('找不到使用者。');
            return self::FAILURE;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("要刪除使用者 {$user->email} 嗎？")) {
                $this->info('已取消。');
                return self::SUCCESS;
            }
        }

        $user->delete();
        $this->info("已刪除使用者: {$user->email}");

        return self::SUCCESS;
    }
}
