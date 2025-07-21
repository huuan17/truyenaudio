<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'action',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
                    ->withTimestamps();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Helper Methods
     */
    public function getFullNameAttribute()
    {
        return $this->module . '.' . $this->action;
    }

    /**
     * Get permission badge class for UI
     */
    public function getBadgeClassAttribute()
    {
        switch ($this->action) {
            case 'create':
                return 'badge-success';
            case 'read':
                return 'badge-info';
            case 'update':
                return 'badge-warning';
            case 'delete':
                return 'badge-danger';
            case 'manage':
                return 'badge-primary';
            default:
                return 'badge-secondary';
        }
    }

    /**
     * Get module icon
     */
    public function getModuleIconAttribute()
    {
        switch ($this->module) {
            case 'stories':
                return 'fas fa-book';
            case 'users':
                return 'fas fa-users';
            case 'roles':
                return 'fas fa-user-shield';
            case 'settings':
                return 'fas fa-cogs';
            case 'chapters':
                return 'fas fa-file-alt';
            case 'authors':
                return 'fas fa-user-edit';
            case 'genres':
                return 'fas fa-tags';
            case 'videos':
                return 'fas fa-video';
            case 'audios':
                return 'fas fa-volume-up';
            default:
                return 'fas fa-circle';
        }
    }

    /**
     * Get action icon
     */
    public function getActionIconAttribute()
    {
        switch ($this->action) {
            case 'create':
                return 'fas fa-plus';
            case 'read':
                return 'fas fa-eye';
            case 'update':
                return 'fas fa-edit';
            case 'delete':
                return 'fas fa-trash';
            case 'manage':
                return 'fas fa-cogs';
            default:
                return 'fas fa-circle';
        }
    }
}
