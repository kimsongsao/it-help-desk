<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Limits a model to the current organization and stamps new records with it.
 *
 * @mixin Model
 */
trait BelongsToOrganization
{
    /**
     * Register the organization scope and creation hook.
     */
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $query): void {
            $tenancy = app(Tenancy::class);

            if ($tenancy->check()) {
                $query->where($query->qualifyColumn('organization_id'), $tenancy->id());
            }
        });

        static::creating(function (Model $model): void {
            $model->organization_id ??= app(Tenancy::class)->id();
        });
    }

    /**
     * Get the organization the record belongs to.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
