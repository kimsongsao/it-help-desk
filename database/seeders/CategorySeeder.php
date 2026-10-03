<?php

namespace Database\Seeders;

use App\Enums\HelpDeskTemplate;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the IT support categories for every organization that has none yet.
     */
    public function run(): void
    {
        Organization::whereDoesntHave('categories')
            ->each(fn (Organization $organization) => HelpDeskTemplate::ItSupport->createCategoriesFor($organization));
    }
}
