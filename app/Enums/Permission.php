<?php

namespace App\Enums;

enum Permission: string
{
    case TicketsViewAll = 'tickets.view_all';
    case TicketsCreate = 'tickets.create';
    case TicketsManage = 'tickets.manage';
    case TicketsAssign = 'tickets.assign';
    case TicketsWork = 'tickets.work';
    case TicketsCommentInternal = 'tickets.comment_internal';
    case TicketsEscalate = 'tickets.escalate';
    case TicketsDelete = 'tickets.delete';
    case ReportsView = 'reports.view';
    case SlaManage = 'sla.manage';
    case WorkflowsManage = 'workflows.manage';
    case CategoriesManage = 'categories.manage';
    case TeamsManage = 'teams.manage';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case OrganizationManage = 'organization.manage';
    case ApiAccess = 'api.access';

    /**
     * Get the translated label for the permission.
     */
    public function label(?string $locale = null): string
    {
        return __(match ($this) {
            self::TicketsViewAll => 'View all tickets',
            self::TicketsCreate => 'Submit tickets',
            self::TicketsManage => 'Change ticket status, priority and category',
            self::TicketsAssign => 'Assign tickets',
            self::TicketsWork => 'Work on tickets (can be assigned)',
            self::TicketsCommentInternal => 'Write internal notes',
            self::TicketsEscalate => 'Escalate tickets',
            self::TicketsDelete => 'Delete tickets',
            self::ReportsView => 'View reports',
            self::SlaManage => 'Manage SLA policies',
            self::WorkflowsManage => 'Manage workflows',
            self::CategoriesManage => 'Manage categories',
            self::TeamsManage => 'Manage teams',
            self::UsersManage => 'Manage users',
            self::RolesManage => 'Manage roles and permissions',
            self::OrganizationManage => 'Manage organization settings',
            self::ApiAccess => 'Create API tokens',
        }, locale: $locale);
    }

    /**
     * Get the group the permission is displayed under.
     */
    public function group(): string
    {
        return match ($this) {
            self::ReportsView => 'Reports',
            self::SlaManage, self::WorkflowsManage, self::CategoriesManage, self::TeamsManage => 'Configuration',
            self::UsersManage, self::RolesManage, self::OrganizationManage, self::ApiAccess => 'Administration',
            default => 'Tickets',
        };
    }

    /**
     * Get the plan feature an organization needs before the permission takes effect.
     */
    public function requiredFeature(): ?Feature
    {
        return match ($this) {
            self::ReportsView => Feature::Reports,
            self::SlaManage, self::TicketsEscalate => Feature::SlaManagement,
            self::WorkflowsManage => Feature::Workflows,
            self::TeamsManage => Feature::Teams,
            self::ApiAccess => Feature::Api,
            default => null,
        };
    }

    /**
     * Get the permissions grouped for display.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::cases() as $permission) {
            $groups[$permission->group()][] = $permission;
        }

        return $groups;
    }
}
