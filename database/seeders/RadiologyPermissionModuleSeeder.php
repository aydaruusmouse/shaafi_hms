<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RadiologyPermissionModuleSeeder extends Seeder
{
    public const PARENT = 'Radiology';

    public const CHILDREN = [
        'Radiology Report Payments',
        'Doctor Suggested Radiology Tests',
    ];

    public const GRANT_ROLES = [
        'Admin',
        'Receptionist',
        'Pharmacist',
        'Lab Technician',
    ];

    public function run(): void
    {
        $tenantIds = DB::table('modules')->select('tenant_id')->distinct()->pluck('tenant_id');
        if ($tenantIds->isEmpty()) {
            $tenantIds = collect([null]);
        }

        foreach ($tenantIds as $tenantId) {
            $parentQuery = DB::table('modules')->where('name', self::PARENT);
            $parentQuery = $tenantId === null
                ? $parentQuery->whereNull('tenant_id')
                : $parentQuery->where('tenant_id', $tenantId);

            $parent = $parentQuery->first();
            if (! $parent) {
                $parentId = DB::table('modules')->insertGetId([
                    'name' => self::PARENT,
                    'is_active' => 1,
                    'is_hidden' => 0,
                    'route' => 'admin.radiology.index',
                    'parent_id' => 99999,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $parentId = $parent->id;
                DB::table('modules')->where('id', $parentId)->update([
                    'parent_id' => 99999,
                    'updated_at' => now(),
                ]);
            }

            foreach (self::CHILDREN as $childName) {
                $childQuery = DB::table('modules')->where('name', $childName);
                $childQuery = $tenantId === null
                    ? $childQuery->whereNull('tenant_id')
                    : $childQuery->where('tenant_id', $tenantId);

                $child = $childQuery->first();

                if ($child) {
                    DB::table('modules')->where('id', $child->id)->update([
                        'parent_id' => $parentId,
                        'is_hidden' => 0,
                        'is_active' => 1,
                        'updated_at' => now(),
                    ]);

                    continue;
                }

                DB::table('modules')->insert([
                    'name' => $childName,
                    'is_active' => 1,
                    'is_hidden' => 0,
                    'route' => 'admin.'.strtolower(str_replace(' ', '_', $childName)).'.index',
                    'parent_id' => $parentId,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->grantFromRadiologyTests();
        $this->grantDefaultRoles();
    }

    private function grantFromRadiologyTests(): void
    {
        $sourceModules = DB::table('modules')->where('name', 'Radiology Tests')->get();

        foreach ($sourceModules as $sourceModule) {
            $sourceRows = DB::table('module_role')
                ->where('module_id', $sourceModule->id)
                ->get();

            $targetQuery = DB::table('modules')->whereIn('name', self::CHILDREN);
            $targets = $sourceModule->tenant_id === null
                ? $targetQuery->whereNull('tenant_id')->get()
                : $targetQuery->where('tenant_id', $sourceModule->tenant_id)->get();

            foreach ($sourceRows as $source) {
                foreach ($targets as $target) {
                    $exists = DB::table('module_role')
                        ->where('role_id', $source->role_id)
                        ->where('module_id', $target->id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    DB::table('module_role')->insert([
                        'role_id' => $source->role_id,
                        'module_id' => $target->id,
                        'can_access' => $source->can_access,
                        'can_create' => $source->can_create,
                        'can_edit' => $source->can_edit,
                        'can_delete' => $source->can_delete,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    private function grantDefaultRoles(): void
    {
        $roleIds = DB::table('departments')
            ->whereIn('name', self::GRANT_ROLES)
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $targets = DB::table('modules')->whereIn('name', self::CHILDREN)->get();

        foreach ($roleIds as $roleId) {
            foreach ($targets as $target) {
                $exists = DB::table('module_role')
                    ->where('role_id', $roleId)
                    ->where('module_id', $target->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                    DB::table('module_role')->insert([
                        'role_id' => $roleId,
                        'module_id' => $target->id,
                        'can_access' => 1,
                        'can_create' => 1,
                        'can_edit' => 1,
                        'can_delete' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        }
    }
}
