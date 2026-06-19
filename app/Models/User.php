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

    public const ROLE_USER = 1;
    public const ROLE_ADMIN = 2;
    public const ROLE_SUPER_ADMIN = 3;
    public const ROLE_SUBADMIN_DIVISION = 4;

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
    'role' => 'integer',
    ];

    public function isAdmin()
    {
        return (int) $this->role === self::ROLE_ADMIN;
    }

    public function isUser()
    {
        return (int) $this->role === self::ROLE_USER;
    }

    public function isSuperAdmin()
    {
        return (int) $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isSubadminDivision()
    {
        return (int) $this->role === self::ROLE_SUBADMIN_DIVISION;
    }
public function canManageLms()
{
    return in_array((int) $this->role, [
        self::ROLE_ADMIN,
        self::ROLE_SUPER_ADMIN,
        self::ROLE_SUBADMIN_DIVISION,
    ], true);
}

//     public function canManageLms()
// {
//     return in_array((int) $this->role, [2, 3, 4], true);
// }

    public function canManageRole()
    {
        return (int) $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function canMonitor()
    {
        return in_array((int) $this->role, [
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
            self::ROLE_SUBADMIN_DIVISION,
        ], true);
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
        $role = (int) $this->role;

        if (!array_key_exists($role, static::roles())) {
            throw new \Exception('Role [' . $this->role . '] is not defined');
        }

        return static::roles()[$role];
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

    $userPermission = $this->accessPermissions()
        ->where('module_key', $moduleKey)
        ->first();

    if ($userPermission) {
        return (bool) $userPermission->can_access;
    }

    $rolePermission = RoleAccessPermission::where('role', $this->role)
        ->where('module_key', $moduleKey)
        ->first();

    if ($rolePermission) {
        return (bool) $rolePermission->can_access;
    }

    return $this->isAdmin();
}
}
