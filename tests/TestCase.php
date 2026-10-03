<?php

namespace Tests;

use App\Models\Organization;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * The organization every test runs in unless it switches to another one.
     */
    protected ?Organization $organization = null;

    /**
     * Run each test inside a fresh organization so factories create tenant-owned records.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $this->organization = Organization::factory()->create(['name' => 'Test Company']);
            app(Tenancy::class)->set($this->organization);
        }
    }
}
