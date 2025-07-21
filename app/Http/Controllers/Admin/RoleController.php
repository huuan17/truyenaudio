<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Add permission middleware later
        // $this->middleware('permission:roles.read')->only(['index', 'show']);
        // $this->middleware('permission:roles.create')->only(['create', 'store']);
        // $this->middleware('permission:roles.update')->only(['edit', 'update']);
        // $this->middleware('permission:roles.delete')->only(['destroy']);
    }

    /**
     * Display a listing of roles
     */
    public function index()
    {
        $roles = Role::orderBy('priority', 'desc')->paginate(10);

        // Manually load data for each role to avoid relationship issues
        $roles->getCollection()->transform(function ($role) {
            // Get permissions count and modules
            $permissionsCount = \DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->count();

            $permissionModules = \DB::table('role_permissions')
                ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('role_permissions.role_id', $role->id)
                ->select('permissions.module')
                ->distinct()
                ->pluck('module')
                ->take(3);

            // Get users count
            $usersCount = \DB::table('user_roles')
                ->where('role_id', $role->id)
                ->count();

            // Set attributes
            $role->permissions_count = $permissionsCount;
            $role->users_count = $usersCount;
            $role->permission_modules = $permissionModules;

            return $role;
        });

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        $role = new Role();
        $permissions = Permission::active()
                                ->orderBy('module')
                                ->orderBy('action')
                                ->get()
                                ->groupBy('module');

        return view('admin.roles.create', compact('role', 'permissions'));
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'priority' => 'required|integer|min:0|max:100',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => Str::slug($request->name, '_'),
            'display_name' => $request->display_name,
            'description' => $request->description,
            'priority' => $request->priority,
            'is_active' => $request->has('is_active'),
        ]);

        // Sync permissions
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('admin.roles.index')
                        ->with('success', 'Role đã được tạo thành công!');
    }

    /**
     * Display the specified role
     */
    public function show(Role $role)
    {
        // Manually load permissions and users to avoid null issues
        $permissions = \DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role_id', $role->id)
            ->select('permissions.*')
            ->get();

        $users = \DB::table('user_roles')
            ->join('users', 'users.id', '=', 'user_roles.user_id')
            ->where('user_roles.role_id', $role->id)
            ->select('users.*', 'user_roles.assigned_at')
            ->get();

        // Group permissions by module
        $groupedPermissions = $permissions->groupBy('module');

        return view('admin.roles.show', compact('role', 'permissions', 'users', 'groupedPermissions'));
    }

    /**
     * Show the form for editing the specified role
     */
    public function edit(Role $role)
    {
        // Get all permissions grouped by module
        $permissions = Permission::active()
                                ->orderBy('module')
                                ->orderBy('action')
                                ->get()
                                ->groupBy('module');

        // Get role's current permissions
        $rolePermissionIds = \DB::table('role_permissions')
            ->where('role_id', $role->id)
            ->pluck('permission_id')
            ->toArray();

        $role->current_permission_ids = $rolePermissionIds;

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'priority' => 'required|integer|min:0|max:100',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => Str::slug($request->name, '_'),
            'display_name' => $request->display_name,
            'description' => $request->description,
            'priority' => $request->priority,
            'is_active' => $request->has('is_active'),
        ]);

        // Sync permissions
        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('admin.roles.index')
                        ->with('success', 'Role đã được cập nhật thành công!');
    }

    /**
     * Remove the specified role
     */
    public function destroy(Role $role)
    {
        // Prevent deletion of super admin role
        if ($role->name === 'super_admin') {
            return redirect()->route('admin.roles.index')
                            ->with('error', 'Không thể xóa role Super Admin!');
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                            ->with('error', 'Không thể xóa role đang được sử dụng bởi ' . $role->users()->count() . ' user(s)!');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
                        ->with('success', 'Role đã được xóa thành công!');
    }

    /**
     * Toggle role status
     */
    public function toggleStatus(Role $role)
    {
        $role->update(['is_active' => !$role->is_active]);

        $status = $role->is_active ? 'kích hoạt' : 'vô hiệu hóa';

        return redirect()->route('admin.roles.index')
                        ->with('success', "Role đã được {$status} thành công!");
    }
}
