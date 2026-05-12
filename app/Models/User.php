<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['first_name', 'last_name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $assembled = trim(implode(' ', array_filter([
                trim((string) $user->first_name),
                trim((string) $user->last_name),
            ])));

            if ($assembled === '') {
                $assembled = trim((string) $user->email) ?: 'User';
            }

            $user->name = $assembled;
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessAdminPanel(): bool
    {
        return in_array((string) $this->role, config('unbeaten_auth.admin_panel_roles', []), true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function roleLabel(): string
    {
        $key = (string) $this->role;

        return config('unbeaten_auth.roles.' . $key) ?? Str::title(str_replace('_', ' ', $key));
    }

    public function initials(): string
    {
        $first = trim((string) $this->first_name);
        $last = trim((string) $this->last_name);

        if ($first !== '' || $last !== '') {
            $a = $first !== '' ? mb_strtoupper(mb_substr($first, 0, 1)) : '';
            $b = $last !== '' ? mb_strtoupper(mb_substr($last, 0, 1)) : '';

            return ($a.$b) !== '' ? $a.$b : ($a !== '' ? $a : '?');
        }

        $parts = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $letters = array_map(
            fn (string $p): string => mb_strtoupper(mb_substr($p, 0, 1)),
            array_slice($parts, 0, 2)
        );

        return $letters !== [] ? implode('', $letters) : '?';
    }
}
