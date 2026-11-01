<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'store_id', 'role', 'is_super_admin', 'is_platform_admin', 'two_factor_enabled'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    public function isPlatformAdmin(): bool
    {
        return $this->is_super_admin === true || $this->is_platform_admin === true;
    }

    public function assignedStores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'platform_admin_store');
    }

    public function memberStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function currentStore(): Store
    {
        if ($this->isSuperAdmin()) {
            throw new \LogicException('Un Super Admin ne possède pas de boutique marchand.');
        }

        return $this->memberStore()->first() ?? $this->store()->firstOrCreate([
            'user_id' => $this->id,
        ], [
            'name' => 'Ma boutique',
            'email' => $this->email,
        ]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
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
            'is_super_admin' => 'boolean',
            'is_platform_admin' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }
}
