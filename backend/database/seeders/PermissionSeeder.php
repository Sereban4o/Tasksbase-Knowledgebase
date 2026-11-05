<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Project permissions
            ['name' => 'view_projects', 'group' => 'project'],
            ['name' => 'create_projects', 'group' => 'project'],
            ['name' => 'edit_projects', 'group' => 'project'],
            ['name' => 'delete_projects', 'group' => 'project'],
            ['name' => 'manage_members', 'group' => 'project'],

            // Issue permissions
            ['name' => 'view_issues', 'group' => 'issue'],
            ['name' => 'create_issues', 'group' => 'issue'],
            ['name' => 'edit_issues', 'group' => 'issue'],
            ['name' => 'delete_issues', 'group' => 'issue'],
            ['name' => 'transition_issues', 'group' => 'issue'],
            ['name' => 'manage_issue_types', 'group' => 'issue'],

            // Board permissions
            ['name' => 'view_boards', 'group' => 'board'],
            ['name' => 'edit_boards', 'group' => 'board'],
            ['name' => 'delete_boards', 'group' => 'board'],

            // Space permissions
            ['name' => 'view_spaces', 'group' => 'space'],
            ['name' => 'create_spaces', 'group' => 'space'],
            ['name' => 'edit_spaces', 'group' => 'space'],
            ['name' => 'delete_spaces', 'group' => 'space'],

            // Page permissions
            ['name' => 'view_pages', 'group' => 'page'],
            ['name' => 'create_pages', 'group' => 'page'],
            ['name' => 'edit_pages', 'group' => 'page'],
            ['name' => 'delete_pages', 'group' => 'page'],
            ['name' => 'publish_pages', 'group' => 'page'],

            // Admin permissions
            ['name' => 'manage_users', 'group' => 'admin'],
            ['name' => 'manage_settings', 'group' => 'admin'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                ['group' => $permission['group']]
            );
        }

        // Create roles
        $roles = [
            ['name' => 'admin', 'description' => 'Full access to everything'],
            ['name' => 'member', 'description' => 'Full access to assigned projects/spaces'],
            ['name' => 'reporter', 'description' => 'Can create and view issues/pages'],
            ['name' => 'viewer', 'description' => 'Read-only access'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }

        // Assign permissions to roles
        $rolePermissions = [
            'admin' => 'all',
            'member' => ['view_projects', 'create_issues', 'edit_issues', 'view_issues', 'transition_issues', 'view_boards', 'edit_boards', 'view_spaces', 'create_pages', 'edit_pages', 'view_pages', 'publish_pages'],
            'reporter' => ['view_projects', 'create_issues', 'view_issues', 'view_spaces', 'create_pages', 'view_pages'],
            'viewer' => ['view_projects', 'view_issues', 'view_spaces', 'view_pages'],
        ];

        $permIds = DB::table('permissions')->pluck('id', 'name')->toArray();
        $roleIds = DB::table('roles')->pluck('id', 'name')->toArray();

        foreach ($rolePermissions as $roleName => $perms) {
            if (!isset($roleIds[$roleName])) {
                continue;
            }

            $roleId = (int) $roleIds[$roleName];
            $permissionNames = $perms === 'all' ? array_keys($permIds) : $perms;

            $rows = [];
            foreach ($permissionNames as $permName) {
                if (isset($permIds[$permName])) {
                    $rows[] = [
                        'role_id' => $roleId,
                        'permission_id' => (int) $permIds[$permName],
                    ];
                }
            }

            if (!empty($rows)) {
                DB::table('role_has_permissions')->insertOrIgnore($rows);
            }
        }
    }
}
