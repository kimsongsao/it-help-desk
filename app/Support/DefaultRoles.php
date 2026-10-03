<?php

namespace App\Support;

use App\Enums\Permission;
use App\Models\Organization;
use App\Models\Role;

/**
 * The built-in roles every organization starts with.
 */
class DefaultRoles
{
    /**
     * Get the definitions of the built-in roles.
     *
     * @return list<array{name: string, slug: string, description: string, permissions: list<Permission>}>
     */
    public static function definitions(): array
    {
        $staff = [
            Permission::TicketsViewAll, Permission::TicketsCreate, Permission::TicketsManage, Permission::TicketsAssign,
            Permission::TicketsWork, Permission::TicketsCommentInternal, Permission::TicketsEscalate, Permission::ApiAccess,
        ];

        return [
            ['name' => 'Administrator', 'slug' => Role::AdministratorSlug, 'description' => 'Full access to every part of the help desk.', 'permissions' => []],
            ['name' => 'Supervisor', 'slug' => 'supervisor', 'description' => 'Leads the support team, receives escalations and configures SLAs and workflows.', 'permissions' => [
                ...$staff, Permission::ReportsView, Permission::SlaManage, Permission::WorkflowsManage, Permission::CategoriesManage,
            ]],
            ['name' => 'Support Agent', 'slug' => 'agent', 'description' => 'Works and resolves tickets.', 'permissions' => $staff],
            ['name' => 'Requester', 'slug' => 'requester', 'description' => 'Employees who submit tickets.', 'permissions' => [Permission::TicketsCreate, Permission::ApiAccess]],
        ];
    }

    /**
     * Create the built-in roles for the organization.
     */
    public static function createFor(Organization $organization): void
    {
        foreach (self::definitions() as $definition) {
            $role = new Role($definition);
            $role->is_system = true;
            $role->organization()->associate($organization);
            $role->save();
        }
    }
}
