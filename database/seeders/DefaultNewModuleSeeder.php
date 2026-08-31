<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DefaultNewModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userTenantId = session('tenant_id', null);

        $input = [
            [
                'name' => 'Odontograms',
                'is_active' => 1,
                'route' => 'odontograms.index',
                'tenant_id' => $userTenantId != null ? $userTenantId : null,
            ],
        ];
        foreach ($input as $data) {
            $module = Module::whereName($data['name'])->whereTenantId($data['tenant_id'])->first();
            if ($module) {
                $module->update(['route' => $data['route']]);
            } else {
                Module::create($data);
            }
        }
    }
}