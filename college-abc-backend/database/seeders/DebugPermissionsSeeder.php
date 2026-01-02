<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Entities\User;
use Illuminate\Support\Facades\Auth;

class DebugPermissionsSeeder extends Seeder
{
    public function run()
    {
        $user = User::where('email', 'admin@college-abc.bf')->first();

        if ($user) {
            $this->command->info("Checking permissions for: {$user->email} (ID: {$user->id})");
            $this->command->info("User Guard Name: " . $user->guard_name);

            // Log user in effectively (mocking)
            Auth::shouldUse('sanctum');

            $roles = $user->roles;
            $this->command->info("Roles count: " . $roles->count());
            foreach ($roles as $role) {
                $this->command->info("- Role: {$role->name} (Guard: {$role->guard_name})");
                $perms = $role->permissions;
                foreach ($perms as $p) {
                    $this->command->info("  -- Perm: {$p->name} (Guard: {$p->guard_name})");
                }
            }

            $directPerms = $user->permissions;
            $this->command->info("Direct Permissions count: " . $directPerms->count());
            foreach ($directPerms as $p) {
                $this->command->info("  -- Perm: {$p->name} (Guard: {$p->guard_name})");
            }

            $hasPerm = $user->hasPermissionTo('view-academic', 'sanctum');
            $this->command->info("Has 'view-academic' (sanctum)? " . ($hasPerm ? 'YES' : 'NO'));
        } else {
            $this->command->error("User not found");
        }
    }
}
