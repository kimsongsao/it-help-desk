<?php

namespace App\Support;

use App\Models\Organization;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Holds the organization (tenant) the current request or job is working for.
 *
 * Models using the BelongsToOrganization trait are automatically limited to this organization.
 */
class Tenancy
{
    private ?Organization $organization = null;

    /**
     * Get the current organization, if one is set.
     */
    public function current(): ?Organization
    {
        return $this->organization;
    }

    /**
     * Get the id of the current organization, if one is set.
     */
    public function id(): ?int
    {
        return $this->organization?->id;
    }

    /**
     * Determine if an organization is set.
     */
    public function check(): bool
    {
        return $this->organization !== null;
    }

    /**
     * Make the organization current.
     */
    public function set(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    /**
     * Run the callback as the given organization, then restore the previous one.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function runAs(Organization $organization, callable $callback): mixed
    {
        $previous = $this->organization;
        $this->organization = $organization;

        try {
            return $callback();
        } finally {
            $this->organization = $previous;
        }
    }

    /**
     * Get an "exists" validation rule limited to the current organization's rows.
     */
    public function exists(string $table, string $column = 'id'): Exists
    {
        return (new Exists($table, $column))->where('organization_id', $this->id());
    }

    /**
     * Get a "unique" validation rule limited to the current organization's rows.
     */
    public function unique(string $table, string $column = 'NULL'): Unique
    {
        return (new Unique($table, $column))->where('organization_id', $this->id());
    }
}
