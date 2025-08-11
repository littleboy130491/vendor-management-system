<?php

namespace Tests;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions only if permission tables exist (avoids errors in tests without DB migrations)
        if (Schema::hasTable('roles') && Schema::hasTable('permissions')) {
            $this->seed(RolePermissionSeeder::class);
        }
    }
}
