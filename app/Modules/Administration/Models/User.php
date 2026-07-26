<?php

namespace App\Modules\Administration\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\CloudflareUpload;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'telephone', 'username', 'is_active', 'role_id', 'avatar', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, CloudflareUpload;

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
            'is_active' => 'boolean'
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user) {
            if (empty($user->username)) {
                $user->username = static::generateUniqueUsername($user->name);
            }

            if (empty($user->role_id)) {
                $user->role_id = Role::where('name', 'user')->value('id') ?? null;
            }
        });
    }

    protected static function generateUniqueUsername(string $fullName): string
    {
        $firstName = Str::of(trim($fullName))->explode(' ')->first() ?: 'user';
        $base = Str::slug($firstName, '');

        do {
            $username = $base . random_int(1000, 9999);
        } while (static::where('username', $username)->exists());

        return $username;
    }

    protected $appends = [
        'avatar_url',
    ];

    /**
     * Get the profile photo URL attribute.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return $this->getImageUrl($this->avatar, 'avatars');
        }
        // Return default avatar
        return $this->defaultProfilePhotoUrl();
    }

    /**
     * Get the default profile photo URL.
     */
    protected function defaultProfilePhotoUrl(): string
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF';
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
