<?php

namespace App\Actions\Organizations;

use App\Enums\HelpDeskTemplate;
use App\Enums\Plan;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Support\DefaultRoles;
use Illuminate\Support\Facades\DB;

class CreateOrganization
{
    /**
     * Create a company workspace with its built-in roles and the template's categories, owned by a new administrator.
     *
     * @param  array{company_name: string, name: string, email: string, password: string, department?: ?string}  $attributes
     */
    public function handle(array $attributes, Plan $plan = Plan::Free, ?string $locale = null, HelpDeskTemplate $template = HelpDeskTemplate::General): User
    {
        return DB::transaction(function () use ($attributes, $plan, $locale, $template): User {
            $organization = new Organization([
                'name' => $attributes['company_name'],
                'default_locale' => $locale ?? config('app.locale'),
            ]);
            $organization->plan = $plan;
            $organization->save();

            DefaultRoles::createFor($organization);
            $template->createCategoriesFor($organization);

            $owner = new User([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'department' => $attributes['department'] ?? null,
            ]);
            $owner->organization()->associate($organization);
            $owner->role()->associate(Role::withoutGlobalScope('organization')
                ->whereBelongsTo($organization)
                ->where('slug', Role::AdministratorSlug)
                ->firstOrFail());
            $owner->locale = $locale;
            $owner->save();

            return $owner;
        });
    }
}
