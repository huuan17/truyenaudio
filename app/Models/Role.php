<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Relationships
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')
                    ->withPivot('assigned_at', 'assigned_by')
                    ->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
                    ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Helper Methods
     */
    public function hasPermission($permission)
    {
        if (is_string($permission)) {
            return $this->permissions()->where('name', $permission)->exists();
        }

        if (is_object($permission)) {
            return $this->permissions()->where('id', $permission->id)->exists();
        }

        return false;
    }

    public function givePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }

        if ($permission && !$this->hasPermission($permission)) {
            $this->permissions()->attach($permission->id);
        }

        return $this;
    }

    public function revokePermissionTo($permission)
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }

        if ($permission) {
            $this->permissions()->detach($permission->id);
        }

        return $this;
    }

    public function syncPermissions($permissions)
    {
        $permissionIds = [];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $perm = Permission::where('name', $permission)->first();
                if ($perm) {
                    $permissionIds[] = $perm->id;
                }
            } elseif (is_numeric($permission)) {
                $permissionIds[] = $permission;
            } elseif (is_object($permission)) {
                $permissionIds[] = $permission->id;
            }
        }

        $this->permissions()->sync($permissionIds);
        return $this;
    }

    /**
     * Check if role is super admin
     */
    public function isSuperAdmin()
    {
        return $this->name === 'super_admin';
    }

    /**
     * Check if role is admin
     */
    public function isAdmin()
    {
        return in_array($this->name, ['super_admin', 'admin']);
    }

    /**
     * Get role badge class for UI
     */
    public function getBadgeClassAttribute()
    {
        switch ($this->name) {
            case 'super_admin':
                return 'badge-danger';
            case 'admin':
                return 'badge-warning';
            case 'editor':
                return 'badge-info';
            case 'user':
                return 'badge-secondary';
            default:
                return 'badge-primary';
        }
    }
}
