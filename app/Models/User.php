<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_RESTAURANT = 'restaurant';

    protected $fillable = [
        'name',
        'username',
        'password',
        'role',
        'site_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isRestaurant(): bool
    {
        return $this->role === self::ROLE_RESTAURANT;
    }
}
