<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createRoles();
        $this->createPermissions();
        $this->assignPermissionsToRoles();
    }

    private function createRoles(): void
    {
        $roles = ['admin', 'tutor', 'student', 'sub_admin'];

        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                Role::create(['name' => $roleName]);
            }
        }
    }

    private function createPermissions(): void
    {
        $permissions = [

            'can-manage-courses',
            'can-manage-badges',
            'can-manage-course-bundles',
            'can-manage-subscriptions',
            'can-manage-forums',
            'can-manage-insights',
            'can-manage-menu',
            'can-manage-option-builder',
            'can-manage-pages',
            'can-manage-email-settings',
            'can-manage-notification-settings',
            'can-manage-languages',
            'can-manage-subjects',
            'can-manage-subject-groups',
            'can-manage-language-translations',
            'can-manage-addons',
            'can-manage-upgrade',
            'can-manage-users',
            'can-manage-identity-verification',
            'can-manage-reviews',
            'can-manage-invoices',
            'can-manage-bookings',
            'can-manage-withdraw-requests',
            'can-manage-commission-settings',
            'can-manage-payment-methods',
            'can-manage-create-blogs',
            'can-manage-all-blogs',
            'can-manage-update-blogs',
            'can-manage-blog-categories',
            'can-manage-course-bundles',
            'can-manage-dispute',
            'can-manage-disputes-list',
            'can-manage-admin-users'
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
        }
    }

    private function assignPermissionsToRoles(): void
    {
        $permissions = [
            'can-manage-courses',
            'can-manage-badges',
            'can-manage-course-bundles',
            'can-manage-subscriptions',
            'can-manage-forums',
            'can-manage-insights',
            'can-manage-menu',
            'can-manage-option-builder',
            'can-manage-pages',
            'can-manage-email-settings',
            'can-manage-notification-settings',
            'can-manage-languages',
            'can-manage-subjects',
            'can-manage-subject-groups',
            'can-manage-language-translations',
            'can-manage-addons',
            'can-manage-upgrade',
            'can-manage-users',
            'can-manage-identity-verification',
            'can-manage-reviews',
            'can-manage-invoices',
            'can-manage-bookings',
            'can-manage-withdraw-requests',
            'can-manage-commission-settings',
            'can-manage-payment-methods',
            'can-manage-create-blogs',
            'can-manage-all-blogs',
            'can-manage-update-blogs',
            'can-manage-blog-categories',
            'can-manage-dispute',
            'can-manage-disputes-list',
            'can-manage-admin-users'
        ];

        // Get or create admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            // Sync all permissions to admin role
            $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
            $adminRole->permissions()->sync($permissionIds);
        }

        // Get or create sub_admin role and give all permissions
        $subAdminRole = Role::where('name', 'sub_admin')->first();
        if ($subAdminRole) {
            $permissionIds = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
            $subAdminRole->permissions()->sync($permissionIds);
        }
    }
}
