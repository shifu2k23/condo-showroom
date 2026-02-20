<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureSuperAdminCommand extends Command
{
    protected $signature = 'super-admin:ensure
        {email : Super admin email address}
        {--name=Super Admin : Display name}
        {--password= : Plain password to set}
        {--dry-run : Print intended changes without writing}';

    protected $description = 'Create or update a super admin account safely.';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $name = trim((string) $this->option('name'));
        $password = (string) ($this->option('password') ?? '');
        $dryRun = (bool) $this->option('dry-run');

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null && $password === '') {
            $this->error('Password is required when creating a new super admin user.');

            return self::FAILURE;
        }

        $payload = [
            'name' => $name !== '' ? $name : 'Super Admin',
            'is_super_admin' => true,
            'is_admin' => false,
            'tenant_id' => null,
            'email_verified_at' => now(),
        ];

        if ($password !== '') {
            $payload['password'] = Hash::make($password);
        }

        if ($dryRun) {
            $action = $user === null ? 'create' : 'update';
            $this->line("[dry-run] Would {$action} super admin: {$email}");

            return self::SUCCESS;
        }

        if ($user === null) {
            $newUser = new User();
            $newUser->forceFill(array_merge(['email' => $email], $payload));
            $newUser->save();
            $this->info("Created super admin: {$email}");
        } else {
            $user->forceFill($payload)->save();
            $this->info("Updated super admin: {$email}");
        }

        $this->line("Super admin ensured for {$email}");

        return self::SUCCESS;
    }
}
