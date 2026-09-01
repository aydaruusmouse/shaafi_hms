<?php

use Database\Seeders\RadiologyPermissionModuleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new RadiologyPermissionModuleSeeder)->run();
    }

    public function down(): void
    {
        // Keep modules; they may already be assigned in roles.
    }
};
