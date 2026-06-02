<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const ROLE_USER              = '1';
    const ROLE_ADMIN             = '2';
    const ROLE_SUPER_ADMIN       = '3';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'id_org_unit',
        'team',
        'division',
        'department',
        'id_emp',
        'id_user',
        'ssohash',
        'sap_code'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isUser()
    {
        return $this->role === self::ROLE_USER;
    }

    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public static function roles()
    {
        return [
            static::ROLE_ADMIN         => 'Administrator',
            static::ROLE_SUPER_ADMIN   => 'Super Admin',
            static::ROLE_USER          => 'User',
        ];
    }

    public function getRoleNameAttribute()
    {
        if (!in_array($this->role, array_keys(static::roles()))) {
            throw new \Exception('Role [' . $this->role . '] is not defined');
        }
        return static::roles()[$this->role];
    }

    public function getInitialsAttribute(): string
    {
        return \Illuminate\Support\Str::initials($this->name);
    }

    public function bookmarkedContents()
    {
        return $this->belongsToMany(Content::class, 'bookmark_contents')
            ->withTimestamps();
    }

    public function bookmarkContents()
    {
        return $this->hasMany(BookmarkContent::class);
    }

        /**
     * Get all news articles published by this user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function news()
    {
        return $this->hasMany(News::class);
    }
}
