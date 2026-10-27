<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Admins\AdminUserService;
use App\Support\PasswordRules;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates the first superadmin, or any admin, from the console (01 · 1.1).
 * The password is never accepted as an argument.
 */
#[Signature('megis:admin:create
    {--role=superadmin : admin vagy superadmin}
    {--invite : Jelszó helyett meghívó e-mailt küld}')]
#[Description('Admin felhasználó létrehozása (01 · 1.1)')]
class CreateAdminCommand extends Command
{
    public function handle(AdminUserService $admins): int
    {
        $role = UserRole::tryFrom((string) $this->option('role'));

        if ($role === null) {
            $this->error(__('console.admin_create.invalid_role'));

            return self::FAILURE;
        }

        $lastName = text(label: __('console.admin_create.last_name'), required: true);
        $firstName = text(label: __('console.admin_create.first_name'), required: true);
        $email = Str::lower(trim(text(
            label: __('console.admin_create.email'),
            required: true,
            validate: fn (string $value): ?string => filter_var(trim($value), FILTER_VALIDATE_EMAIL) ? null : __('console.admin_create.invalid_email'),
        )));

        $existing = User::query()->firstWhere('email', $email);

        if ($existing !== null && ! confirm(__('console.admin_create.existing', ['role' => $role->label()]))) {
            $this->warn(__('console.admin_create.cancelled'));

            return self::FAILURE;
        }

        $data = ['last_name' => $lastName, 'first_name' => $firstName, 'email' => $email];

        if ($this->option('invite')) {
            $admins->invite($data, $role, null);
            $this->info(__('console.admin_create.invited', ['email' => $email]));

            return self::SUCCESS;
        }

        $plainPassword = $this->askForPassword();

        $user = $existing ?? new User;
        $user->fill($data);
        $user->role = $role;
        $user->password = $plainPassword;
        $user->activated_at ??= now();
        $user->save();

        $this->info(__('console.admin_create.created', ['email' => $email, 'role' => $role->label()]));

        return self::SUCCESS;
    }

    private function askForPassword(): string
    {
        while (true) {
            $plainPassword = password(label: __('console.admin_create.password'), required: true, hint: PasswordRules::hint());
            $confirmation = password(label: __('console.admin_create.password_confirmation'), required: true);

            $validator = Validator::make(
                ['password' => $plainPassword, 'password_confirmation' => $confirmation],
                ['password' => ['confirmed', PasswordRules::rule()]],
            );

            if ($validator->passes()) {
                return $plainPassword;
            }

            $this->error($validator->errors()->first('password'));
        }
    }
}
