<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OdontogramModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNotNull('tenant_id')->get();
        foreach ($users as $data) {
            $input = [
                    'name' => 'Odontograms',
                    'is_active' => 1,
                    'route' => 'odontograms.index',
                    'tenant_id' => $data->tenant_id != null ? $data->tenant_id : null,
                ];
            $module = Module::whereName($input['name'])->whereTenantId($input['tenant_id'])->first();
            if ($module) {
                $module->update(['route' => $input['route']]);
            } else {
                Module::create($input);
            }
        }
    }
}
