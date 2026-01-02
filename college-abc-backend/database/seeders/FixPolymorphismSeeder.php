<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixPolymorphismSeeder extends Seeder
{
    public function run()
    {
        $targetModel = 'Modules\Core\Entities\User';
        $oldModel = 'App\Models\User';

        // 1. Fix Tokens
        $affectedTokens = DB::table('personal_access_tokens')
            ->where('tokenable_type', $oldModel)
            ->update(['tokenable_type' => $targetModel]);

        $this->command->info("Updated $affectedTokens tokens to use $targetModel.");

        // 2. Fix Roles assignment
        $affectedRoles = DB::table('model_has_roles')
            ->where('model_type', $oldModel)
            ->update(['model_type' => $targetModel]);

        $this->command->info("Updated $affectedRoles role assignments to use $targetModel.");

        // 3. Fix Permissions assignment
        $affectedPerms = DB::table('model_has_permissions')
            ->where('model_type', $oldModel)
            ->update(['model_type' => $targetModel]);

        $this->command->info("Updated $affectedPerms permission assignments to use $targetModel.");

        // Check verification
        $admin = DB::table('users')->where('email', 'admin@college-abc.bf')->first();
        if ($admin) {
            $tokenCount = DB::table('personal_access_tokens')
                ->where('tokenable_id', $admin->id)
                ->where('tokenable_type', $targetModel)
                ->count();
            $this->command->info("Admin now has $tokenCount valid tokens with correct type.");
        }
    }
}
