<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserAccessPermission;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const ROLE_USER              = '1';
    const ROLE_ADMIN             = '2';
    const ROLE_SUPER_ADMIN       = '3';
    const ROLE_SUBADMIN_DIVISION = '4';

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
        'sap_code',
        'role',
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

    public function isSubadminDivision()
    {
        return $this->role === self::ROLE_SUBADMIN_DIVISION;
    }

    /**
     * Boleh mengakses menu & fitur Manage LMS (Admin Training + Super Admin).
     */
    public function canManageLms()
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }

    /**
     * Boleh mengakses Manage Role (hanya Super Admin).
     */
    public function canManageRole()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Boleh mengakses Monitoring (Admin Training, Super Admin, Subadmin Division).
     */
    public function canMonitor()
    {
        return in_array(
            $this->role,
            [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN, self::ROLE_SUBADMIN_DIVISION],
            true
        );
    }

    public static function roles()
    {
        return [
            static::ROLE_ADMIN              => 'Administrator',
            static::ROLE_SUPER_ADMIN        => 'Super Admin',
            static::ROLE_USER               => 'User',
            static::ROLE_SUBADMIN_DIVISION  => 'Subadmin Division',
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


    public function commentLikes()
    {
        return $this->hasMany(CommentLike::class);
    }

    public function commentReplies()
    {
        return $this->hasMany(CommentReply::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
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

    public function accessPermissions()
{
    return $this->hasMany(UserAccessPermission::class);
}

public function hasAccessPermission(string $moduleKey): bool
{
    return $this->accessPermissions()
        ->where('module_key', $moduleKey)
        ->where('can_access', true)
        ->exists();
}

public function canAccessManageModule(string $moduleKey): bool
{
    if ($this->isSuperAdmin()) {
        return true;
    }

    $permission = $this->accessPermissions()
        ->where('module_key', $moduleKey)
        ->first();

    if ($permission) {
        return (bool) $permission->can_access;
    }

    return $this->isAdmin();
}
}
