<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds roles and permissions for the admin guard.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Define all permissions grouped by resource ───────────────────────
        $permissions = [
            // group_name => [permission names]
            'dashboard' => [
                'view dashboard',
            ],
            'admin' => [
                'view admins',
                'create admin',
                'edit admin',
                'delete admin',
            ],
            'roles' => [
                'view roles',
                'create role',
                'edit role',
                'delete role',
            ],
            'clubs' => [
                'view clubs',
                'create club',
                'edit club',
                'delete club',
                'verify club',
            ],
            'players' => [
                'view players',
                'edit player',
                'delete player',
                'toggle player status',
            ],
            'settings' => [
                'view settings',
                'edit settings',
            ],
        ];

        // ─── Create permissions with group_name ───────────────────────────────
        foreach ($permissions as $group => $names) {
            foreach ($names as $name) {
                Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'admin'],
                    ['group_name' => $group]
                );
            }
        }

        // ─── Create Roles ─────────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'admin']);
        $adminRole  = Role::firstOrCreate(['name' => 'admin',      'guard_name' => 'admin']);
        $support    = Role::firstOrCreate(['name' => 'support',    'guard_name' => 'admin']);

        // ─── Assign permissions to roles ──────────────────────────────────────

        // Super Admin — gets ALL permissions
        $superAdmin->syncPermissions(Permission::where('guard_name', 'admin')->get());

        // Admin — everything except role/admin management and settings delete
        $adminPermissions = [
            'view dashboard',
            'view admins',
            'view clubs',  'create club',  'edit club',  'verify club',
            'view players','edit player', 'toggle player status',
            'view settings', 'edit settings',
        ];
        $adminRole->syncPermissions($adminPermissions);

        // Support — read-only access to clubs and players
        $supportPermissions = [
            'view dashboard',
            'view clubs',
            'view players',
        ];
        $support->syncPermissions($supportPermissions);

        // ─── Assign superadmin role to the existing super admin account ───────
        $superAdminUser = Admin::where('email', 'admin@gmail.com')->first();
        if ($superAdminUser) {
            $superAdminUser->syncRoles(['superadmin']);
        }

        $this->command->info('✅ Roles & Permissions seeded successfully!');
        $this->command->table(
            ['Role', 'Permissions Count'],
            [
                ['superadmin', $superAdmin->permissions()->count()],
                ['admin',      $adminRole->permissions()->count()],
                ['support',    $support->permissions()->count()],
            ]
        );
    }
}
