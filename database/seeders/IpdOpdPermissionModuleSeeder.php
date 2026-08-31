<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IpdOpdPermissionModuleSeeder extends Seeder
{
    public const IPD_PARENT = 'IPD';

    public const OPD_PARENT = 'OPD';

    public const IPD_CHILDREN = [
        'IPD Patients',
        'IPD Bills',
        'IPD Charges',
        'IPD Consultant Instruction',
        'IPD Consultation',
        'IPD Diagnosis',
        'IPD Nurse Notes',
        'IPD Operations',
        'IPD Overview',
        'IPD Payments',
        'IPD Prescriptions',
        'IPD Test Results',
        'IPD Timelines',
        'IPD Vital Information',
        'IPD/OPD',
    ];

    public const OPD_CHILDREN = [
        'OPD Patients',
        'OPD Consultation',
        'OPD Diagnosis',
        'OPD Overview',
        'OPD Prescriptions',
        'OPD Test Results',
        'OPD Timelines',
        'OPD Visits',
        'OPD Vital Information',
    ];

    public const VISIBLE_MODULES = [
        'IPD Patients',
        'OPD Patients',
    ];

    public function run(): void
    {
        $tenantIds = DB::table('modules')->select('tenant_id')->distinct()->pluck('tenant_id');

        if ($tenantIds->isEmpty()) {
            $tenantIds = collect([null]);
        }

        foreach ($tenantIds as $tenantId) {
            $this->ensureGroup($tenantId, self::IPD_PARENT, self::IPD_CHILDREN);
            $this->ensureGroup($tenantId, self::OPD_PARENT, self::OPD_CHILDREN);
        }

        $this->grantFromExistingParentModules();
        $this->grantAdminFullAccess();
    }

    private function ensureGroup(?string $tenantId, string $parentName, array $children): void
    {
        $parentQuery = DB::table('modules')->where('name', $parentName);
        $parentQuery = $tenantId === null
            ? $parentQuery->whereNull('tenant_id')
            : $parentQuery->where('tenant_id', $tenantId);

        $parent = $parentQuery->first();

        if (! $parent) {
            $parentId = DB::table('modules')->insertGetId([
                'name' => $parentName,
                'is_active' => 1,
                'is_hidden' => 1,
                'route' => $this->generateRoute($parentName),
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

        foreach ($children as $childName) {
            $childQuery = DB::table('modules')->where('name', $childName);
            $childQuery = $tenantId === null
                ? $childQuery->whereNull('tenant_id')
                : $childQuery->where('tenant_id', $tenantId);

            $child = $childQuery->first();
            $isHidden = in_array($childName, self::VISIBLE_MODULES, true) ? 0 : 1;

            if ($child) {
                DB::table('modules')->where('id', $child->id)->update([
                    'parent_id' => $parentId,
                    'is_hidden' => $isHidden,
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('modules')->insert([
                'name' => $childName,
                'is_active' => 1,
                'is_hidden' => $isHidden,
                'route' => $this->generateRoute($childName),
                'parent_id' => $parentId,
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function grantFromExistingParentModules(): void
    {
        $sourceNames = ['IPD Patients', 'OPD Patients'];
        $sourceRows = DB::table('module_role')
            ->join('modules', 'module_role.module_id', '=', 'modules.id')
            ->whereIn('modules.name', $sourceNames)
            ->select(
                'module_role.role_id',
                'module_role.can_access',
                'module_role.can_create',
                'module_role.can_edit',
                'module_role.can_delete',
                'modules.name as module_name'
            )
            ->get()
            ->unique(fn ($row) => $row->role_id.'|'.$row->module_name);

        foreach ($sourceRows as $source) {
            $namesToGrant = $source->module_name === 'IPD Patients'
                ? self::IPD_CHILDREN
                : self::OPD_CHILDREN;

            $targetModules = DB::table('modules')->whereIn('name', $namesToGrant)->get();

            foreach ($targetModules as $target) {
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

    private function grantAdminFullAccess(): void
    {
        $admin = DB::table('departments')->where('name', 'Admin')->first();
        if (! $admin) {
            return;
        }

        $names = array_merge(self::IPD_CHILDREN, self::OPD_CHILDREN);
        $modules = DB::table('modules')->whereIn('name', $names)->get();

        foreach ($modules as $module) {
            $exists = DB::table('module_role')
                ->where('role_id', $admin->id)
                ->where('module_id', $module->id)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('module_role')->insert([
                'role_id' => $admin->id,
                'module_id' => $module->id,
                'can_access' => 1,
                'can_create' => 1,
                'can_edit' => 1,
                'can_delete' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function generateRoute(string $moduleName): string
    {
        $key = strtolower(str_replace([' ', '/'], ['_', '_'], $moduleName));

        return "admin.{$key}.index";
    }
}
