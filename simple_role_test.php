<?php

require_once 'vendor/autoload.php';

// Load Laravel app
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SIMPLE ROLE TEST ===\n";

try {
    // Test basic counts
    $roleCount = \App\Models\Role::count();
    $permissionCount = \App\Models\Permission::count();
    echo "Roles: {$roleCount}, Permissions: {$permissionCount}\n";
    
    // Test controller logic
    echo "\nTesting controller logic...\n";
    
    $roles = \App\Models\Role::withCount(['permissions', 'users'])
                ->orderBy('priority', 'desc')
                ->take(3)
                ->get();
    
    echo "Roles loaded: " . $roles->count() . "\n";
    
    // Force load permissions
    $roles->transform(function ($role) {
        $role->load(['permissions:id,name,display_name,module,action', 'users:id,name,email']);
        return $role;
    });
    
    foreach ($roles as $role) {
        echo "\nRole: {$role->name}\n";
        echo "  Permissions count: " . ($role->permissions_count ?? 0) . "\n";
        echo "  Users count: " . ($role->users_count ?? 0) . "\n";
        echo "  Permissions loaded: " . ($role->relationLoaded('permissions') ? 'Yes' : 'No') . "\n";
        
        if ($role->permissions) {
            echo "  Permissions collection count: " . $role->permissions->count() . "\n";
            if ($role->permissions->count() > 0) {
                $modules = $role->permissions->pluck('module')->unique();
                echo "  Modules: " . $modules->implode(', ') . "\n";
            }
        } else {
            echo "  Permissions: null\n";
        }
    }
    
    echo "\n✅ Test completed successfully!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>
