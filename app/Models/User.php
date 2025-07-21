<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
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
        'password' => 'hashed',
    ];

    /**
     * Relationships
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
                    ->withPivot('assigned_at', 'assigned_by')
                    ->withTimestamps();
    }

    public function assignedRoles()
    {
        return $this->hasMany(UserRole::class, 'assigned_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        // Since we don't have is_active column, return all users
        return $query;
    }

    public function scopeWithRole($query, $role)
    {
        return $query->whereHas('roles', function($q) use ($role) {
            $q->where('name', $role);
        });
    }

    /**
     * Role & Permission Methods
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles()->where('roles.name', $role)->exists();
        }

        if (is_object($role)) {
            return $this->roles()->where('roles.id', $role->id)->exists();
        }

        return false;
    }

    public function hasAnyRole($roles)
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    public function hasPermission($permission)
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role && !$this->hasRole($role)) {
            $this->roles()->attach($role->id, [
                'assigned_at' => now(),
                'assigned_by' => auth()->id()
            ]);
        }

        return $this;
    }

    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role) {
            $this->roles()->detach($role->id);
        }

        return $this;
    }

    public function syncRoles($roles)
    {
        $roleIds = [];

        foreach ($roles as $role) {
            if (is_string($role)) {
                $r = Role::where('name', $role)->first();
                if ($r) {
                    $roleIds[$r->id] = [
                        'assigned_at' => now(),
                        'assigned_by' => auth()->id()
                    ];
                }
            } elseif (is_numeric($role)) {
                $roleIds[$role] = [
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id()
                ];
            } elseif (is_object($role)) {
                $roleIds[$role->id] = [
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id()
                ];
            }
        }

        $this->roles()->sync($roleIds);
        return $this;
    }

    /**
     * Legacy role methods (for backward compatibility)
     */
    public function isAdmin()
    {
        return $this->role === 'admin' || $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Helper Methods
     */
    public function getFullNameAttribute()
    {
        if ($this->first_name && $this->last_name) {
            return $this->first_name . ' ' . $this->last_name;
        }
        return $this->name;
    }

    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        // Default avatar using initials
        $initials = collect(explode(' ', $this->full_name))
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->implode('');

        return "https://ui-avatars.com/api/?name={$initials}&size=200&background=007bff&color=ffffff";
    }

    public function getRoleBadgeAttribute()
    {
        if ($this->isSuperAdmin()) {
            return '<span class="badge badge-danger">Super Admin</span>';
        }

        if ($this->isAdmin()) {
            return '<span class="badge badge-warning">Admin</span>';
        }

        $primaryRole = $this->roles()->orderBy('priority', 'desc')->first();
        if ($primaryRole) {
            return '<span class="badge ' . $primaryRole->badge_class . '">' . $primaryRole->display_name . '</span>';
        }

        return '<span class="badge badge-secondary">User</span>';
    }

    public function getPrimaryRoleAttribute()
    {
        return $this->roles()->orderBy('priority', 'desc')->first();
    }

    public function getStatusBadgeAttribute()
    {
        // Since we don't have is_active column, assume all users are active
        return '<span class="badge badge-success">Active</span>';
    }

    /**
     * Update last login info
     */
    public function updateLastLogin($ip = null)
    {
        // Since we don't have last_login_at and last_login_ip columns, do nothing for now
        return $this;
    }
}
