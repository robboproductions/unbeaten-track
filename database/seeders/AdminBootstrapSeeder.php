<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminBootstrapSeeder extends Seeder
{
    /**
     * Create or update users from config/unbeaten_auth.php bootstrap_users.
     *
     * Set AUTH_BOOTSTRAP_* in .env, then run: php artisan db:seed --class=AdminBootstrapSeeder
     */
    public function run(): void
    {
        $validRoles = array_keys(config('unbeaten_auth.roles', []));

        foreach (config('unbeaten_auth.bootstrap_users', []) as $row) {
            $email = $row['email'] ?? null;
            $password = $row['password'] ?? null;
            if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
                continue;
            }

            $role = (string) ($row['role'] ?? User::ROLE_ADMIN);
            if (! in_array($role, $validRoles, true)) {
                continue;
            }

            [$firstName, $lastName] = $this->splitDisplayName((string) ($row['name'] ?? 'User'));

            User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make($password),
                    'role' => $role,
                ]
            );
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitDisplayName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['User', ''];
        }

        $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = (string) ($parts[0] ?? 'User');
        $last = trim(implode(' ', array_slice($parts, 1)));

        return [$first, $last];
    }
}
