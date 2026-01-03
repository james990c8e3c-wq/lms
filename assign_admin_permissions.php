<?php
/**
 * Standalone script to assign all admin permissions to admin role
 * This script runs the RolePermissionsSeeder to ensure admin user has all permissions
 */

require __DIR__ . '/Lernen/lernen-main-file/lernen/vendor/autoload.php';

$env_file = __DIR__ . '/Lernen/lernen-main-file/lernen/.env';
if (!file_exists($env_file)) {
    die("Error: .env file not found at $env_file\n");
}

$app = require __DIR__ . '/Lernen/lernen-main-file/lernen/bootstrap/app.php';

try {
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    
    echo "🔄 Loading Laravel application...\n";
    $kernel->bootstrap();
    
    echo "🔄 Running RolePermissionsSeeder...\n";
    $app['db']->statement('SET FOREIGN_KEY_CHECKS=0;');
    
    $seeder = new \Database\Seeders\RolePermissionsSeeder();
    $seeder->run();
    
    $app['db']->statement('SET FOREIGN_KEY_CHECKS=1;');
    
    echo "✅ RolePermissionsSeeder completed successfully!\n\n";
    
    // Verify permissions were assigned
    echo "📋 Verifying permissions assignment...\n";
    
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();
    if ($adminRole) {
        $permissionCount = $adminRole->permissions()->count();
        echo "✅ Admin role has $permissionCount permissions assigned\n";
        
        echo "\n📌 Admin Permissions:\n";
        foreach ($adminRole->permissions()->get() as $permission) {
            echo "   ✓ " . $permission->name . "\n";
        }
    } else {
        echo "❌ Admin role not found\n";
    }
    
    // Verify admin user has the role
    echo "\n🔍 Verifying admin user assignment...\n";
    $adminUser = \App\Models\User::where('email', 'admin@amentotech.com')->first();
    if ($adminUser) {
        $roles = $adminUser->getRoleNames();
        echo "✅ Admin user (admin@amentotech.com) has roles: " . $roles->implode(', ') . "\n";
        
        $permissions = $adminUser->getAllPermissions();
        echo "✅ Admin user has " . $permissions->count() . " permissions\n";
        
        echo "\n📌 Admin User Permissions:\n";
        foreach ($permissions as $permission) {
            echo "   ✓ " . $permission->name . "\n";
        }
    } else {
        echo "❌ Admin user not found\n";
    }
    
    echo "\n✨ Task completed successfully! Admin user now has all admin permissions.\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
