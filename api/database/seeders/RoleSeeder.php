<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = config('rmcp.permissions', []);

        $roles = [
            'Super Admin' => ['*'],
            'Company Admin' => [
                'clients.*',
                'companies.*',
                'documents.*',
                'incidents.*',
                'cases.*',
                'directors.*',
                'shareholders.*',
                'beneficial_owners.*',
                'tasks.*',
                'communications.*',
                'audit_logs.view',
                'roles.view',
            ],
            'Compliance Officer' => [
                'clients.view', 'clients.create', 'clients.edit',
                'documents.view', 'documents.create', 'documents.edit', 'documents.delete',
                'incidents.view', 'incidents.create', 'incidents.edit',
                'cases.view', 'cases.create', 'cases.edit', 'cases.submit',
                'directors.view', 'directors.create', 'directors.edit',
                'shareholders.view', 'shareholders.create', 'shareholders.edit',
                'beneficial_owners.view', 'beneficial_owners.create', 'beneficial_owners.edit',
                'tasks.view', 'tasks.create', 'tasks.edit',
                'communications.view', 'communications.create', 'communications.edit',
                'audit_logs.view',
            ],
            'Employee' => [
                'clients.view',
                'companies.view',
                'documents.view', 'documents.create',
                'incidents.view', 'incidents.create',
                'cases.view',
                'tasks.view',
                'communications.view', 'communications.create',
            ],
            'Individual User' => [
                'clients.view',
                'documents.view',
                'cases.view',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            if ($permissions !== ['*']) {
                $permissions = array_values(array_filter($permissions, static fn (string $permission): bool => in_array($permission, $allPermissions, true) || str_ends_with($permission, '.*')));
            }

            Role::query()->updateOrCreate(
                ['role_name' => $roleName],
                ['permissions' => $permissions]
            );
        }
    }
}
