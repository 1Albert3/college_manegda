<?php

namespace Modules\Attendance\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AttendancePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Permissions pour les présences
            'view-attendances',
            'mark-attendances',
            'manage-attendances',

            // Permissions pour les justifications
            'view-justifications',
            'submit-justifications',
            'manage-justifications',

            // Permissions pour les rapports
            'view-attendance-reports',
            'export-attendance-data',

            // Permissions administratives
            'send-attendance-notifications',
            'approve-justifications',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('Attendance permissions created successfully');
    }
}
