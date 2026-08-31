<?php

namespace App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources;

use App\Filament\HospitalAdmin\Clusters\HospitalCharge;
use App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources\ChargeTypeResource\Pages;
use App\Models\ChargeCategory;
use App\Models\ChargeType;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChargeTypeResource extends Resource
{
    protected static ?string $model = ChargeType::class;

    protected static ?string $cluster = HospitalCharge::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Charge Categories')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Charge Categories')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.charge_type.charge_types');
    }

    public static function getLabel(): string
    {
        return __('messages.charge_type.charge_types');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Charge Categories');
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Charge Categories');
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Charge Categories');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['Admin', 'Receptionist']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('messages.charge_type.name').':')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Charge Categories')) {
            abort(404);
        }

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $tenantId = auth()->user()->tenant_id;

                return $query->where(function ($inner) use ($tenantId) {
                    $inner->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
                });
            })
            ->paginated([10, 25, 50])
            ->defaultSort('id')
            ->columns([
                TextColumn::make('name')
                    ->label(__('messages.charge_type.name'))
                    ->searchable()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->modalWidth('md')
                    ->successNotificationTitle(__('messages.charge_type.updated')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (ChargeType $record) {
                        if (is_null($record->tenant_id) || $record->id <= 5) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.charge_type.cannot_delete_default'))
                                ->send();
                        }

                        if (ChargeCategory::where('charge_type', $record->id)->exists()) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.charge_category_not_found'))
                                ->send();
                        }

                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.charge_type.deleted'))
                            ->send();
                    }),
            ])
            ->recordAction(null)
            ->actionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageChargeTypes::route('/'),
        ];
    }
}
