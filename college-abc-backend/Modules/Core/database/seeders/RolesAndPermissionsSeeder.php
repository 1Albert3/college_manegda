<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view-users',
            'create-users',
            'update-users',
            'delete-users',
            'activate-users',
            'deactivate-users',

            // Role management
            'view-roles',
            'create-roles',
            'update-roles',
            'delete-roles',
            'assign-roles',
            'remove-roles',

            // Permission management
            'view-permissions',
            'manage-permissions',

            // Student management
            'view-students',
            'create-students',
            'update-students',
            'delete-students',

            // Teacher management
            'view-teachers',
            'create-teachers',
            'update-teachers',
            'delete-teachers',

            // Academic management
            'view-academic',
            'manage-academic',

            // Attendance management
            'view-attendance',
            'mark-attendance',
            'manage-attendance',

            // Grade management
            'view-grades',
            'enter-grades',
            'manage-grades',

            // Finance management
            'view-finance',
            'create-payments',
            'manage-payments',

            // Communication
            'send-sms',
            'send-emails',
            'view-communications',

            // Reports
            'view-reports',
            'generate-reports',
            'export-reports',

            // Configuration
            'manage-settings',
            'view-activity-logs',

            // Service management
            'manage-canteen',
            'manage-transport',
            'manage-health',

            // Document management
            'view-documents',
            'manage-documents',

            // Alumni management
            'view-alumni',
            'manage-alumni',

            // E-learning
            'manage-courses',
            'manage-assignments',
            'manage-quizzes',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Create roles and assign permissions
        $rolePermissions = [
            'super_admin' => Permission::all(), // All permissions

            'director' => [
                // User management
                'view-users', 'create-users', 'update-users', 'activate-users', 'deactivate-users',
                // Role management (limited)
                'view-roles', 'assign-roles',
                // Academic oversight
                'view-academic', 'manage-academic',
                // All student/teacher management
                'view-students', 'create-students', 'update-students', 'delete-students',
                'view-teachers', 'create-teachers', 'update-teachers', 'delete-teachers',
                // Grades, attendance, finance oversight
                'view-grades', 'manage-grades',
                'view-attendance', 'manage-attendance',
                'view-finance', 'manage-payments',
                // Communications
                'send-sms', 'send-emails', 'view-communications',
                // Reports
                'view-reports', 'generate-reports', 'export-reports',
                // Services
                'manage-canteen', 'manage-transport', 'manage-health',
                // Documents
                'view-documents', 'manage-documents',
                // Activity logs
                'view-activity-logs',
            ],

            'teacher' => [
                // Limited user view
                'view-users',
                // Student management (read/update)
                'view-students', 'update-students',
                // Academic management
                'view-academic', 'manage-academic',
                // Grades and attendance
                'view-grades', 'enter-grades',
                'view-attendance', 'mark-attendance',
                // Communications
                'send-sms', 'send-emails',
                // Basic reports
                'view-reports',
                // E-learning
                'manage-courses', 'manage-assignments', 'manage-quizzes',
                // Documents
                'view-documents',
            ],

            'parent' => [
                // Can view own child's data
                'view-students', // Limited to own children via relationships
                'view-grades', // Limited to own children
                'view-attendance', // Limited to own children
                'view-finance', // Limited to own payments
                'view-reports', // Limited reports
                'view-communications', // Receive communications
                // Cannot manage anything else
            ],

            'student' => [
                // Can view own profile and results
                // Very limited permissions - mostly read-only
                'view-grades', // Own grades
                'view-attendance', // Own attendance
                'view-reports', // Basic reports
                'view-communications', // Receive messages
                // E-learning access
                'manage-courses', // Limited to own courses
                'manage-assignments', // Limited to submitting assignments
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);

            if (is_array($perms)) {
                $role->syncPermissions($perms);
            } elseif ($perms instanceof \Illuminate\Database\Eloquent\Collection) {
                $role->syncPermissions($perms);
            }
        }

        $this->command->info('Roles and permissions seeded successfully');
        $this->command->info('Created ' . count($permissions) . ' permissions');
        $this->command->info('Created ' . count($rolePermissions) . ' roles');
    }
}
