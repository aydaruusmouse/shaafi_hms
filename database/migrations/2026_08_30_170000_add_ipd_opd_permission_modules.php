<?php

use Database\Seeders\IpdOpdPermissionModuleSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new IpdOpdPermissionModuleSeeder)->run();
    }

    public function down(): void
    {
        $names = array_merge(
            IpdOpdPermissionModuleSeeder::IPD_CHILDREN,
            IpdOpdPermissionModuleSeeder::OPD_CHILDREN,
            [IpdOpdPermissionModuleSeeder::IPD_PARENT, IpdOpdPermissionModuleSeeder::OPD_PARENT]
        );

        $keep = IpdOpdPermissionModuleSeeder::VISIBLE_MODULES;

        \Illuminate\Support\Facades\DB::table('modules')
            ->whereIn('name', $names)
            ->whereNotIn('name', $keep)
            ->delete();
    }
};
