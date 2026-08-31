<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Settings\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Module;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends Resource
{
    protected static ?string $model = Department::class;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $cluster = Settings::class;
    protected static ?string $navigationLabel = 'Role and Permission';
    protected static ?string $slug = 'roles';
    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('Super Admin') ?? false;
    }

    public static function getPluralModelLabel(): string
    {
        return 'Role and Permission';
    }

    public static function getModelLabel(): string
    {
        return 'Role';
    }


public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Role Info
            Forms\Components\Section::make('Role Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true, table: 'departments', column: 'name')
                        ->label('Role Name')
                        ->placeholder('e.g., Admin, Doctor, Nurse'),
                ])
                ->columns(2),

            // Module Access Control with table format
            Forms\Components\Section::make('Module Access Control')
                ->schema(self::getModuleAccessSchema())
                ->afterStateHydrated(function ($component, $state, $record) {
                    if ($record) {
                        $modulesById = Module::withoutGlobalScopes()->get()->keyBy('id');
                        $canonicalIds = $modulesById->groupBy('name')->map(fn ($group) => $group->first()->id);

                        $modulePermissions = [];
                        foreach (DB::table('module_role')->where('role_id', $record->id)->get() as $row) {
                            $module = $modulesById->get($row->module_id);
                            if (! $module) {
                                continue;
                            }

                            $canonicalId = $canonicalIds[$module->name] ?? $row->module_id;
                            $existing = $modulePermissions[$canonicalId] ?? [
                                'view' => false,
                                'create' => false,
                                'edit' => false,
                                'delete' => false,
                            ];

                            $modulePermissions[$canonicalId] = [
                                'view' => $existing['view'] || (bool) $row->can_access,
                                'create' => $existing['create'] || (bool) $row->can_create,
                                'edit' => $existing['edit'] || (bool) $row->can_edit,
                                'delete' => $existing['delete'] || (bool) $row->can_delete,
                            ];
                        }

                        $component->state([
                            'name' => $record->name,
                            'module_permissions' => $modulePermissions,
                        ]);
                    }
                }),
        ]);
}

/**
 * Get module access schema - separated to prevent multiple executions
 */
protected static function getModuleAccessSchema(): array
{
    // Load modules once and cache statically for this request
    static $cachedModules = null;
    
    if ($cachedModules === null) {
        $cachedModules = Module::withoutGlobalScopes()
            ->with(['children' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
    }
    
    $allModules = $cachedModules;
    $moduleRules = config('module_permissions');
    $childModules = $allModules->filter(fn ($module) => $module->parent_id && (int) $module->parent_id !== 99999);
    $groupedChildNames = $childModules->unique('name')->pluck('name');
    $parentModules = $allModules
        ->whereIn('parent_id', [null, 99999])
        ->unique('name')
        ->filter(function ($module) use ($groupedChildNames) {
            if ((int) $module->parent_id === 99999) {
                return true;
            }

            return ! $groupedChildNames->contains($module->name);
        })
        ->sortBy('name');
    
    $moduleCards = [];
    
    foreach ($parentModules as $parentModule) {
        $parentIds = $allModules
            ->where('name', $parentModule->name)
            ->whereIn('parent_id', [null, 99999])
            ->pluck('id');

        $relatedChildren = $childModules
            ->whereIn('parent_id', $parentIds)
            ->unique('name')
            ->sortBy(fn ($module) => modulePermissionLabel($module->name));
        
        $moduleSchema = [];
        
        // Table header
        $moduleSchema[] = Forms\Components\Grid::make()
            ->schema([
                Forms\Components\Placeholder::make("header_module_{$parentModule->id}")
                    ->label('')
                    ->content('')
                    ->columnSpan(2),
                Forms\Components\Placeholder::make("header_view_{$parentModule->id}")
                    ->label('View')
                    ->content('')
                    ->columnSpan(1),
                Forms\Components\Placeholder::make("header_create_{$parentModule->id}")
                    ->label('Create')
                    ->content('')
                    ->columnSpan(1),
                Forms\Components\Placeholder::make("header_edit_{$parentModule->id}")
                    ->label('Edit')
                    ->content('')
                    ->columnSpan(1),
                Forms\Components\Placeholder::make("header_delete_{$parentModule->id}")
                    ->label('Delete')
                    ->content('')
                    ->columnSpan(1),
            ])
            ->columns(6)
            ->extraAttributes(['class' => 'font-bold border-b border-gray-300 pb-1']);
        
        // Parent module row
        if($parentModule->parent_id != 99999){
            $moduleSchema[] = self::getModuleTableRow($parentModule, null, $moduleRules, modulePermissionLabel($parentModule->name));
        }
        
        // Child modules
        if ($relatedChildren->isNotEmpty()) {
            foreach ($relatedChildren as $childModule) {
                $moduleSchema[] = self::getModuleTableRow($childModule, null, $moduleRules, modulePermissionLabel($childModule->name));
            }
        }
        
        $moduleCards[] = Forms\Components\Section::make($parentModule->name)
            ->schema($moduleSchema)
            ->collapsible()
            ->collapsed(fn () => true) // Always collapsed by default
            ->compact()
            ->columnSpan(1);
    }
    
    return [
        Forms\Components\Grid::make()
            ->schema($moduleCards)
            ->columns([
                'default' => 1,
                'md' => 2,
            ])
            ->extraAttributes(['class' => 'gap-4']),
    ];
}


protected static function getModuleTableRow($module, $record, $moduleRules, $label = null): Forms\Components\Component
{
    $moduleId = $module->id;
    $label = $label ?: $module->name;

    return Forms\Components\Grid::make()
        ->schema([
            // Module name on left (2 columns for better spacing)
            Forms\Components\Placeholder::make("module_name_{$moduleId}")
                ->label('')
                ->content($label)
                ->helperText(($label=='Patient Queue')? fn () => new \Illuminate\Support\HtmlString('
                    <div class="text-xs text-gray-500 mt-1">
                        To check In/Out requires edit rights
                    </div>
                '):'')
                ->extraAttributes(['class' => 'text-sm font-medium'])
                ->columnSpan(2),
            
            // Checkboxes on right (4 columns - one for each permission)
            Forms\Components\Checkbox::make("module_permissions.{$moduleId}.view")
                ->label('')
                ->default(false)
                ->columnSpan(1),
            
            Forms\Components\Checkbox::make("module_permissions.{$moduleId}.create")
                ->label('')
                ->default(false)
                ->columnSpan(1),
            
            Forms\Components\Checkbox::make("module_permissions.{$moduleId}.edit")
                ->label('')
                ->default(false)
                ->columnSpan(1),
            
            Forms\Components\Checkbox::make("module_permissions.{$moduleId}.delete")
                ->label('')
                ->default(false)
                ->columnSpan(1),
        ])
        ->columns(6)
        ->extraAttributes(['class' => 'border-b border-gray-100 py-2']);
}
    /**
     * Get permission checkboxes for a specific module
     */
    private static function getModulePermissions($module, $record, $moduleRules): array
    {
        $roleName = $record->name ?? '';
        $allowedActions = $moduleRules[$roleName][$module->name] ?? [];
        
        $checkboxes = [];

        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            $checkboxes[] = Forms\Components\Checkbox::make("module_permissions.{$module->id}.{$action}")
                ->label(ucfirst($action))
                ->default(function () use ($record, $module, $action) {
                    if (!$record) return false;
                    
                    $permission = DB::table('module_role')
                        ->where('role_id', $record->id)
                        ->where('module_id', $module->id)
                        ->first();
                    
                    if (!$permission) return false;
                    
                    return match($action) {
                        'view' => (bool) $permission->can_access,
                        'create' => (bool) $permission->can_create,
                        'edit' => (bool) $permission->can_edit,
                        'delete' => (bool) $permission->can_delete,
                        default => false,
                    };
                })
                ->disabled(fn () => !empty($allowedActions) && !in_array($action, $allowedActions))
                ->hint(fn () => !empty($allowedActions) && !in_array($action, $allowedActions) ? 'Not allowed' : '');
        }

        return $checkboxes;
    }



    public static function syncModuleAccess($data, $record)
    {
        $permissions = $data['module_permissions'] ?? [];

        DB::transaction(function () use ($permissions, $record) {
            DB::table('module_role')->where('role_id', $record->id)->delete();

            $modulesById = Module::withoutGlobalScopes()->get()->keyBy('id');
            $idsByName = Module::withoutGlobalScopes()->get()->groupBy('name')->map(fn ($group) => $group->pluck('id'));

            $rowsByModuleId = [];

            foreach ($permissions as $moduleId => $perms) {
                $view = (bool) ($perms['view'] ?? false);
                $create = (bool) ($perms['create'] ?? false);
                $edit = (bool) ($perms['edit'] ?? false);
                $delete = (bool) ($perms['delete'] ?? false);

                if (! ($view || $create || $edit || $delete)) {
                    continue;
                }

                $moduleName = $modulesById->get($moduleId)?->name;
                $targetIds = $moduleName ? ($idsByName[$moduleName] ?? collect([$moduleId])) : collect([$moduleId]);

                foreach ($targetIds as $targetId) {
                    $existing = $rowsByModuleId[$targetId] ?? [
                        'can_access' => 0,
                        'can_create' => 0,
                        'can_edit' => 0,
                        'can_delete' => 0,
                    ];

                    $rowsByModuleId[$targetId] = [
                        'role_id' => $record->id,
                        'module_id' => $targetId,
                        'can_access' => ($existing['can_access'] || $view) ? 1 : 0,
                        'can_create' => ($existing['can_create'] || $create) ? 1 : 0,
                        'can_edit' => ($existing['can_edit'] || $edit) ? 1 : 0,
                        'can_delete' => ($existing['can_delete'] || $delete) ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (! empty($rowsByModuleId)) {
                DB::table('module_role')->insert(array_values($rowsByModuleId));
            }
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Role Name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime('M j, Y')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->visible(fn ($record) => $record?->id > 10),
            ])
            /*->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])*/;
    }

    public static function getRelations(): array
    {
        return [];
    }
    public static function canCreate(): bool
    {
        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
