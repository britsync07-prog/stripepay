<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Seed the platform admin account.
     *
     * Credentials come from config (config/admin.php, backed by ADMIN_* env) —
     * never hardcoded, so a known password can't leak through the repo/git
     * history. If no password is provided, a strong random one is generated and
     * printed once.
     */
    public function run(): void
    {
        $email = config('admin.seed.email');

        if (blank($email)) {
            $this->command?->warn('AdminSeeder skipped: set ADMIN_EMAIL (and optionally ADMIN_PASSWORD) to seed the admin.');

            return;
        }

        $password = config('admin.seed.password');
        $generated = blank($password);

        if ($generated) {
            $password = Str::password(20);
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('admin.seed.name', 'Admin'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        )->assignRole('admin');

        if ($generated) {
            $this->command?->warn("Admin created with a generated password: {$password}");
            $this->command?->warn('Store it now — it will not be shown again.');
        } else {
            $this->command?->info("Admin seeded: {$email}");
        }
    }
}
