<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUG ROLE PERMISSIONS ===\n";

// Test 1: Check basic data
echo "1. 📊 Basic Data Check:\n";
try {
    $roleCount = \App\Models\Role::count();
    $permissionCount = \App\Models\Permission::count();
    $rolePermissionCount = \DB::table('role_permissions')->count();
    
    echo "  Roles: {$roleCount}\n";
    echo "  Permissions: {$permissionCount}\n";
    echo "  Role-Permission relations: {$rolePermissionCount}\n";
    
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Test individual role
echo "\n2. 🔍 Individual Role Test:\n";
try {
    $role = \App\Models\Role::first();
    if ($role) {
        echo "  Testing role: {$role->name}\n";
        
        // Test without eager loading
        echo "  Without eager loading:\n";
        $permissions = $role->permissions;
        echo "    Permissions type: " . gettype($permissions) . "\n";
        if ($permissions) {
            echo "    Permissions class: " . get_class($permissions) . "\n";
            echo "    Permissions count: " . $permissions->count() . "\n";
        } else {
            echo "    Permissions: null\n";
        }
        
        // Test with eager loading
        echo "  With eager loading:\n";
        $roleWithPermissions = \App\Models\Role::with('permissions')->find($role->id);
        $eagerPermissions = $roleWithPermissions->permissions;
        echo "    Eager permissions type: " . gettype($eagerPermissions) . "\n";
        echo "    Eager permissions class: " . get_class($eagerPermissions) . "\n";
        echo "    Eager permissions count: " . ($eagerPermissions ? $eagerPermissions->count() : 'null') . "\n";
        
        // Test relationship query
        echo "  Direct relationship query:\n";
        $directPermissions = $role->permissions()->get();
        echo "    Direct permissions count: " . $directPermissions->count() . "\n";
        
    } else {
        echo "  ❌ No roles found\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    echo "  Stack trace: " . $e->getTraceAsString() . "\n";
}

// Test 3: Test controller query
echo "\n3. 🎮 Controller Query Test:\n";
try {
    echo "  Testing controller query...\n";
    
    $roles = \App\Models\Role::with(['permissions' => function($query) {
                    $query->select('permissions.id', 'permissions.name', 'permissions.display_name', 'permissions.module', 'permissions.action');
                }, 'users' => function($query) {
                    $query->select('users.id', 'users.name', 'users.email');
                }])
                ->withCount(['permissions', 'users'])
                ->orderBy('priority', 'desc')
                ->take(3)
                ->get();
    
    echo "  Query executed successfully\n";
    echo "  Roles returned: " . $roles->count() . "\n";
    
    foreach ($roles as $role) {
        echo "    Role: {$role->name}\n";
        echo "      Permissions loaded: " . ($role->relationLoaded('permissions') ? 'Yes' : 'No') . "\n";
        echo "      Permissions count: " . ($role->permissions ? $role->permissions->count() : 'null') . "\n";
        echo "      Permissions_count attribute: {$role->permissions_count}\n";
        
        if ($role->permissions && $role->permissions->count() > 0) {
            echo "      First permission: " . $role->permissions->first()->name . "\n";
            echo "      Can take(3): ";
            try {
                $taken = $role->permissions->take(3);
                echo "Yes (" . $taken->count() . " items)\n";
            } catch (\Exception $e) {
                echo "No - " . $e->getMessage() . "\n";
            }
        }
        echo "    ---\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    echo "  Stack trace: " . $e->getTraceAsString() . "\n";
}

// Test 4: Test pivot table
echo "\n4. 🔗 Pivot Table Test:\n";
try {
    $pivotData = \DB::table('role_permissions')
                    ->join('roles', 'roles.id', '=', 'role_permissions.role_id')
                    ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                    ->select('roles.name as role_name', 'permissions.name as permission_name')
                    ->take(10)
                    ->get();
    
    echo "  Pivot relationships found: " . $pivotData->count() . "\n";
    foreach ($pivotData as $pivot) {
        echo "    {$pivot->role_name} -> {$pivot->permission_name}\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Test 5: Test permission attributes
echo "\n5. 🏷️ Permission Attributes Test:\n";
try {
    $permission = \App\Models\Permission::first();
    if ($permission) {
        echo "  Testing permission: {$permission->name}\n";
        echo "    Module: {$permission->module}\n";
        echo "    Action: {$permission->action}\n";
        echo "    Module icon: {$permission->module_icon}\n";
        echo "    Action icon: {$permission->action_icon}\n";
        echo "    Badge class: {$permission->badge_class}\n";
        echo "    Full name: {$permission->full_name}\n";
    } else {
        echo "  ❌ No permissions found\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Test 6: Simulate view rendering
echo "\n6. 🎨 View Simulation Test:\n";
try {
    $role = \App\Models\Role::with('permissions')->first();
    if ($role) {
        echo "  Testing view logic for role: {$role->name}\n";
        
        // Test permissions count
        $permissionsCount = $role->permissions_count ?? $role->permissions->count();
        echo "    Permissions count: {$permissionsCount}\n";
        
        // Test permissions existence
        $hasPermissions = $role->permissions && $role->permissions->count() > 0;
        echo "    Has permissions: " . ($hasPermissions ? 'Yes' : 'No') . "\n";
        
        if ($hasPermissions) {
            // Test take() method
            try {
                $taken = $role->permissions->take(3);
                echo "    Take(3) successful: " . $taken->count() . " items\n";
                
                foreach ($taken as $permission) {
                    echo "      - {$permission->module} ({$permission->action})\n";
                }
            } catch (\Exception $e) {
                echo "    Take(3) failed: " . $e->getMessage() . "\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Debug completed!\n";

?>
