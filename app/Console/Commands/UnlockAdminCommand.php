<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Auth\AdminAuthenticator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Lifts the lockout after 10 failed admin logins (01 · 1.1, 1.2).
 */
#[Signature('megis:admin:unlock {email : A zárolt admin e-mail címe}')]
#[Description('Zárolt admin fiók feloldása (01 · 1.1)')]
class UnlockAdminCommand extends Command
{
    public function handle(AdminAuthenticator $authenticator): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $user = User::query()->admins()->firstWhere('email', $email);

        if ($user === null) {
            $this->error(__('console.admin_unlock.not_found', ['email' => $email]));

            return self::FAILURE;
        }

        $authenticator->unlock($user);
        $this->info(__('console.admin_unlock.unlocked', ['email' => $email]));

        return self::SUCCESS;
    }
}
