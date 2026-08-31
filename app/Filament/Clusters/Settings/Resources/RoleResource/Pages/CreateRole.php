<?php

namespace App\Filament\Clusters\Settings\Resources\RoleResource\Pages;

use App\Filament\Clusters\Settings\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;


class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
    /*protected function afterCreate(): void
    {
        // Get module permissions from form data
        $modulePermissions = $this->data['module_permissions'] ?? [];

        // Insert updated permissions
        foreach ($modulePermissions as $moduleId => $perms) {
            DB::table('module_role')->insert([
                'role_id'    => $this->record->id,
                'module_id'  => $moduleId,
                'can_access' => $perms['view'] ?? false,
                'can_create' => $perms['create'] ?? false,
                'can_edit'   => $perms['edit'] ?? false,
                'can_delete' => $perms['delete'] ?? false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }*/
    protected function handleRecordCreation(array $data): \App\Models\Department
    {
        // Remove module_permissions from the data before saving to DB
        $permissions = $data['module_permissions'] ?? [];
        unset($data['module_permissions']);

        $data['guard_name'] = $data['guard_name'] ?? 'web';
        $data['is_active'] = $data['is_active'] ?? true;

        $record = static::getModel()::create($data);

        RoleResource::syncModuleAccess(['module_permissions' => $permissions], $record);

        return $record;
    }
}
