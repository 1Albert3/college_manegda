<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Modules\Core\Entities\User;

class FixPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔧 Fixing Permissions...');

        $guards = ['web', 'sanctum']; // Create for both guards to be safe with Spatie/Sanctum
        $rolesNames = ['super_admin', 'admin'];
        $permissionsNames = ['view-academic', 'manage-academic'];

        foreach ($guards as $guard) {
            // 1. Ensure permissions exist
            foreach ($permissionsNames as $perm) {
                Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
            }

            // 2. Assign to Roles
            foreach ($rolesNames as $roleName) {
                $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
                $role->givePermissionTo($permissionsNames);
            }
        }

        $this->command->info('✓ Permissions assigned to roles (web & sanctum)');

        // 3. Ensure Admin User has role
        $adminEmail = 'admin@college-abc.bf';
        $admin = User::where('email', $adminEmail)->first();
        
        if ($admin) {
            // Fetch the role object for 'web' (default) or 'sanctum'
            // Usually assigning by name checks the user's guard.
            // Let's explicitly assign for both if possible or just use string name after creating both.
            
            // Because we created 'admin' for 'sanctum' above, this should now succeed even if Spatie infers 'sanctum'.
            $admin->assignRole('admin'); 
            
            $this->command->info("✓ Role 'admin' assigned to {$adminEmail}");
        } else {
            $this->command->warn("User {$adminEmail} not found.");
        }
        
    }
}
