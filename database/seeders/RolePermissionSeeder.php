<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // Stories
            ['name' => 'stories.create', 'display_name' => 'Tạo truyện', 'description' => 'Có thể tạo truyện mới', 'module' => 'stories', 'action' => 'create'],
            ['name' => 'stories.read', 'display_name' => 'Xem truyện', 'description' => 'Có thể xem danh sách và chi tiết truyện', 'module' => 'stories', 'action' => 'read'],
            ['name' => 'stories.update', 'display_name' => 'Sửa truyện', 'description' => 'Có thể chỉnh sửa thông tin truyện', 'module' => 'stories', 'action' => 'update'],
            ['name' => 'stories.delete', 'display_name' => 'Xóa truyện', 'description' => 'Có thể xóa truyện', 'module' => 'stories', 'action' => 'delete'],
            ['name' => 'stories.manage', 'display_name' => 'Quản lý truyện', 'description' => 'Toàn quyền quản lý truyện', 'module' => 'stories', 'action' => 'manage'],

            // Chapters
            ['name' => 'chapters.create', 'display_name' => 'Tạo chương', 'description' => 'Có thể tạo chương mới', 'module' => 'chapters', 'action' => 'create'],
            ['name' => 'chapters.read', 'display_name' => 'Xem chương', 'description' => 'Có thể xem nội dung chương', 'module' => 'chapters', 'action' => 'read'],
            ['name' => 'chapters.update', 'display_name' => 'Sửa chương', 'description' => 'Có thể chỉnh sửa nội dung chương', 'module' => 'chapters', 'action' => 'update'],
            ['name' => 'chapters.delete', 'display_name' => 'Xóa chương', 'description' => 'Có thể xóa chương', 'module' => 'chapters', 'action' => 'delete'],
            ['name' => 'chapters.manage', 'display_name' => 'Quản lý chương', 'description' => 'Toàn quyền quản lý chương', 'module' => 'chapters', 'action' => 'manage'],

            // Users
            ['name' => 'users.create', 'display_name' => 'Tạo user', 'description' => 'Có thể tạo user mới', 'module' => 'users', 'action' => 'create'],
            ['name' => 'users.read', 'display_name' => 'Xem user', 'description' => 'Có thể xem danh sách user', 'module' => 'users', 'action' => 'read'],
            ['name' => 'users.update', 'display_name' => 'Sửa user', 'description' => 'Có thể chỉnh sửa thông tin user', 'module' => 'users', 'action' => 'update'],
            ['name' => 'users.delete', 'display_name' => 'Xóa user', 'description' => 'Có thể xóa user', 'module' => 'users', 'action' => 'delete'],
            ['name' => 'users.manage', 'display_name' => 'Quản lý user', 'description' => 'Toàn quyền quản lý user', 'module' => 'users', 'action' => 'manage'],

            // Roles
            ['name' => 'roles.create', 'display_name' => 'Tạo role', 'description' => 'Có thể tạo role mới', 'module' => 'roles', 'action' => 'create'],
            ['name' => 'roles.read', 'display_name' => 'Xem role', 'description' => 'Có thể xem danh sách role', 'module' => 'roles', 'action' => 'read'],
            ['name' => 'roles.update', 'display_name' => 'Sửa role', 'description' => 'Có thể chỉnh sửa role', 'module' => 'roles', 'action' => 'update'],
            ['name' => 'roles.delete', 'display_name' => 'Xóa role', 'description' => 'Có thể xóa role', 'module' => 'roles', 'action' => 'delete'],
            ['name' => 'roles.manage', 'display_name' => 'Quản lý role', 'description' => 'Toàn quyền quản lý role và phân quyền', 'module' => 'roles', 'action' => 'manage'],

            // Authors
            ['name' => 'authors.create', 'display_name' => 'Tạo tác giả', 'description' => 'Có thể tạo tác giả mới', 'module' => 'authors', 'action' => 'create'],
            ['name' => 'authors.read', 'display_name' => 'Xem tác giả', 'description' => 'Có thể xem danh sách tác giả', 'module' => 'authors', 'action' => 'read'],
            ['name' => 'authors.update', 'display_name' => 'Sửa tác giả', 'description' => 'Có thể chỉnh sửa thông tin tác giả', 'module' => 'authors', 'action' => 'update'],
            ['name' => 'authors.delete', 'display_name' => 'Xóa tác giả', 'description' => 'Có thể xóa tác giả', 'module' => 'authors', 'action' => 'delete'],
            ['name' => 'authors.manage', 'display_name' => 'Quản lý tác giả', 'description' => 'Toàn quyền quản lý tác giả', 'module' => 'authors', 'action' => 'manage'],

            // Genres
            ['name' => 'genres.create', 'display_name' => 'Tạo thể loại', 'description' => 'Có thể tạo thể loại mới', 'module' => 'genres', 'action' => 'create'],
            ['name' => 'genres.read', 'display_name' => 'Xem thể loại', 'description' => 'Có thể xem danh sách thể loại', 'module' => 'genres', 'action' => 'read'],
            ['name' => 'genres.update', 'display_name' => 'Sửa thể loại', 'description' => 'Có thể chỉnh sửa thể loại', 'module' => 'genres', 'action' => 'update'],
            ['name' => 'genres.delete', 'display_name' => 'Xóa thể loại', 'description' => 'Có thể xóa thể loại', 'module' => 'genres', 'action' => 'delete'],
            ['name' => 'genres.manage', 'display_name' => 'Quản lý thể loại', 'description' => 'Toàn quyền quản lý thể loại', 'module' => 'genres', 'action' => 'manage'],

            // Videos
            ['name' => 'videos.create', 'display_name' => 'Tạo video', 'description' => 'Có thể tạo video mới', 'module' => 'videos', 'action' => 'create'],
            ['name' => 'videos.read', 'display_name' => 'Xem video', 'description' => 'Có thể xem danh sách video', 'module' => 'videos', 'action' => 'read'],
            ['name' => 'videos.update', 'display_name' => 'Sửa video', 'description' => 'Có thể chỉnh sửa video', 'module' => 'videos', 'action' => 'update'],
            ['name' => 'videos.delete', 'display_name' => 'Xóa video', 'description' => 'Có thể xóa video', 'module' => 'videos', 'action' => 'delete'],
            ['name' => 'videos.manage', 'display_name' => 'Quản lý video', 'description' => 'Toàn quyền quản lý video', 'module' => 'videos', 'action' => 'manage'],

            // Audios
            ['name' => 'audios.create', 'display_name' => 'Tạo audio', 'description' => 'Có thể tạo audio mới', 'module' => 'audios', 'action' => 'create'],
            ['name' => 'audios.read', 'display_name' => 'Xem audio', 'description' => 'Có thể xem danh sách audio', 'module' => 'audios', 'action' => 'read'],
            ['name' => 'audios.update', 'display_name' => 'Sửa audio', 'description' => 'Có thể chỉnh sửa audio', 'module' => 'audios', 'action' => 'update'],
            ['name' => 'audios.delete', 'display_name' => 'Xóa audio', 'description' => 'Có thể xóa audio', 'module' => 'audios', 'action' => 'delete'],
            ['name' => 'audios.manage', 'display_name' => 'Quản lý audio', 'description' => 'Toàn quyền quản lý audio', 'module' => 'audios', 'action' => 'manage'],

            // Settings
            ['name' => 'settings.read', 'display_name' => 'Xem cài đặt', 'description' => 'Có thể xem cài đặt hệ thống', 'module' => 'settings', 'action' => 'read'],
            ['name' => 'settings.update', 'display_name' => 'Sửa cài đặt', 'description' => 'Có thể thay đổi cài đặt hệ thống', 'module' => 'settings', 'action' => 'update'],
            ['name' => 'settings.manage', 'display_name' => 'Quản lý cài đặt', 'description' => 'Toàn quyền quản lý cài đặt hệ thống', 'module' => 'settings', 'action' => 'manage'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Create Roles
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Toàn quyền quản trị hệ thống',
                'priority' => 100,
                'permissions' => Permission::pluck('name')->toArray() // All permissions
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'Quản trị viên hệ thống',
                'priority' => 90,
                'permissions' => [
                    'stories.manage', 'chapters.manage', 'authors.manage', 'genres.manage',
                    'videos.manage', 'audios.manage', 'users.read', 'users.update',
                    'settings.read', 'settings.update'
                ]
            ],
            [
                'name' => 'editor',
                'display_name' => 'Editor',
                'description' => 'Biên tập viên nội dung',
                'priority' => 70,
                'permissions' => [
                    'stories.create', 'stories.read', 'stories.update',
                    'chapters.create', 'chapters.read', 'chapters.update',
                    'authors.create', 'authors.read', 'authors.update',
                    'genres.read', 'videos.read', 'audios.read'
                ]
            ],
            [
                'name' => 'moderator',
                'display_name' => 'Moderator',
                'description' => 'Kiểm duyệt viên',
                'priority' => 50,
                'permissions' => [
                    'stories.read', 'stories.update',
                    'chapters.read', 'chapters.update',
                    'authors.read', 'genres.read',
                    'videos.read', 'audios.read'
                ]
            ],
            [
                'name' => 'user',
                'display_name' => 'User',
                'description' => 'Người dùng thông thường',
                'priority' => 10,
                'permissions' => [
                    'stories.read', 'chapters.read', 'authors.read', 'genres.read'
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            // Assign permissions to role
            $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }

        // Assign Super Admin role to first user (if exists)
        $firstUser = User::first();
        if ($firstUser) {
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole && !$firstUser->hasRole('super_admin')) {
                $firstUser->assignRole($superAdminRole);
                echo "Assigned Super Admin role to user: {$firstUser->email}\n";
            }
        }

        echo "Roles and Permissions seeded successfully!\n";
    }
}
