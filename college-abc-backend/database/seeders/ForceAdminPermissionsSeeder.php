<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Entities\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ForceAdminPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Permissions
        $permissions = [
            'view-academic',
            'manage-academic',
            'view-students',
            'manage-students',
            'view-grades',
            'manage-grades',
            'view-finance',
            'manage-finance',
        ];

        // Create Permissions for 'sanctum' guard specifically for API
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'sanctum']);
            // Also create for web just in case, but assign sanctum to user
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $user = User::where('email', 'admin@college-abc.bf')->first();

        if ($user) {
            $this->command->info("Found user: {$user->email}");

            // Create Role for 'sanctum'
            $roleSanctum = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);

            // Sync permissions to the Role
            $roleSanctum->syncPermissions(
                Permission::whereIn('name', $permissions)->where('guard_name', 'sanctum')->get()
            );

            // Assign Role to User - Specifying the role object with the correct guard
            // Sometimes Spatie checks the model guard. 
            // If the User model 'guard_name' attribute is not 'sanctum', it might complain.
            // We can force it by using the string method sometimes, but object is safer if guards match.

            // Let's try assigning by name + guard
            try {
                $user->assignRole($roleSanctum);
                $this->command->info("Assigned 'admin' (sanctum) role to user.");
            } catch (\Exception $e) {
                $this->command->warn("Could not assign sanctum role directly: " . $e->getMessage());
                // Try assigning specific permissions directly as fallback
            }

            // Direct permissions assignment for Sanctum
            foreach ($permissions as $perm) {
                try {
                    $p = Permission::where('name', $perm)->where('guard_name', 'sanctum')->first();
                    $user->givePermissionTo($p);
                } catch (\Exception $e) {
                    // Ignore if already exists or mismatch
                }
            }

            $this->command->info("Done forcing permissions.");
        } else {
            $this->command->error("User not found");
        }
    }
}
