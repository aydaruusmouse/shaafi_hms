<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Resources;

use App\Filament\HospitalAdmin\Clusters\BedManagement;
use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedTypeResource\Pages;
use App\Models\Bed;
use App\Models\BedType;
use App\Models\IpdPatientDepartment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BedTypeResource extends Resource
{
    protected static ?string $model = BedType::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = BedManagement::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Bed Types')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Bed Types')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.bed_type.bed_types');
    }

    public static function getLabel(): string
    {
        return __('messages.bed_type.bed_types');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Nurse']) && getModuleAccess('Bed Types')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Nurse']) && getModuleAccess('Bed Types')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Nurse']) && getModuleAccess('Bed Types')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Nurse'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->validationAttribute(__('messages.bed.bed_type'))
                    ->placeholder(__('messages.bed.bed_type'))
                    ->label(__('messages.bed.bed_type').':')
                    ->maxLength(160),
                Forms\Components\Textarea::make('description')
                    ->placeholder(__('messages.bed_type.description'))
                    ->label(__('messages.bed_type.description').':'),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Nurse']) && ! getModuleAccess('Bed Types')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->color('primary')
                    ->sortable()
                    ->label(__('messages.bed.bed_type'))
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.bed_type_updated')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (BedType $record) {
                        if (! canAccessRecord(BedType::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.bed_type_not_found'))
                                ->send();
                        }
                        $bed = Bed::whereBedType($record->id)->exists();
                        $ipdPatientDepartment = IpdPatientDepartment::whereBedTypeId($record->id)->exists();

                        if ($bed || $ipdPatientDepartment) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.bed_type_cant_deleted'))
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.bed_type_deleted'))
                            ->send();
                    }),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->recordAction(null)
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBedTypes::route('/'),
            'view' => Pages\ViewBedType::route('/{record}'),
        ];
    }
}
