<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources;

use App\Filament\HospitalAdmin\Clusters\Services;
use App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceResource\Pages;
use App\Models\Ambulance;
use App\Models\AmbulanceCall;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redirect;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class AmbulanceResource extends Resource
{
    protected static ?string $model = Ambulance::class;

    protected static ?string $cluster = Services::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Ambulances')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Ambulances')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.ambulances');
    }

    public static function getLabel(): string
    {
        return __('messages.ambulances');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('vehicle_number')
                            ->label(__('messages.ambulance.vehicle_number').':')
                            ->validationMessages([
                                'unique' => __('messages.ambulance.vehicle_number').' '.__('messages.common.is_already_exists'),
                            ])
                            ->placeholder(__('messages.ambulance.vehicle_number'))
                            ->required()
                            ->validationAttribute(__('messages.ambulance.vehicle_number'))
                            ->maxLength(191),
                        TextInput::make('vehicle_model')
                            ->label(__('messages.ambulance.vehicle_model').':')
                            ->placeholder(__('messages.ambulance.vehicle_model'))
                            ->required()
                            ->validationAttribute(__('messages.ambulance.vehicle_model'))
                            ->maxLength(191),
                        TextInput::make('year_made')
                            ->label(__('messages.ambulance.year_made').':')
                            ->placeholder(__('messages.ambulance.year_made'))
                            ->required()
                            ->validationAttribute(__('messages.ambulance.year_made'))
                            ->numeric()
                            ->maxLength(191),
                        TextInput::make('driver_name')
                            ->label(__('messages.ambulance.driver_name').':')
                            ->placeholder(__('messages.ambulance.driver_name'))
                            ->required()
                            ->validationAttribute(__('messages.ambulance.driver_name'))
                            ->maxLength(191),
                        PhoneInput::make('driver_contact')
                            ->label(__('messages.ambulance.driver_contact').':')
                            ->defaultCountry('IN')
                            ->required()
                            ->rules(function ($get) {
                                return [
                                    'required',
                                    'phone:AUTO,'.strtoupper($get('prefix_code')),
                                ];
                            })
                            ->validationMessages([
                                'phone' => __('messages.common.invalid_number'),
                            ]),
                        TextInput::make('driver_license')
                            ->label(__('messages.ambulance.driver_license').':')
                            ->placeholder(__('messages.ambulance.driver_license'))
                            ->required()
                            ->validationAttribute(__('messages.ambulance.driver_license'))
                            ->maxLength(191),
                        Textarea::make('note')
                            ->label(__('messages.ambulance.note').':')
                            ->placeholder(__('messages.ambulance.note'))
                            ->rows(2)
                            ->maxLength(255),
                        Select::make('vehicle_type')
                            ->label(__('messages.ambulance.vehicle_type').':')
                            ->options([
                                2 => __('messages.ambulance.owned'),
                                1 => __('messages.ambulance.contractual'),
                            ])
                            ->preload()
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.ambulance.vehicle_type').' '.__('messages.fields.required'),
                            ]),
                        Toggle::make('is_available')
                            ->live()
                            ->label(__('messages.ambulance.is_available'))
                            ->default(1),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist']) && ! getModuleAccess('Ambulances')) {
            abort(404);
        }

        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(getLoggedInUser()->tenant_id);

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    TextColumn::make('vehicle_number')
                        ->label(__('messages.ambulance.vehicle_number'))
                        ->sortable()
                        ->words(5)
                        ->searchable(),
                    TextColumn::make('vehicle_model')
                        ->label(__('messages.ambulance.vehicle_model'))
                        ->sortable()
                        ->words(5)
                        ->searchable(),
                    TextColumn::make('year_made')
                        ->label(__('messages.ambulance.year_made'))
                        ->sortable()
                        ->words(5)
                        ->searchable(),
                    TextColumn::make('driver_name')
                        ->label(__('messages.ambulance.driver_name'))
                        ->sortable()
                        ->words(5)
                        ->searchable(),
                    TextColumn::make('driver_license')
                        ->label(__('messages.ambulance.driver_license'))
                        ->sortable()
                        ->words(5)
                        ->searchable(),
                    TextColumn::make('driver_contact')
                        ->label(__('messages.ambulance.driver_contact'))
                        ->sortable()
                        ->words(5)
                        ->searchable(),
                    TextColumn::make('vehicle_type')
                        ->label(__('messages.ambulance.vehicle_type'))
                        ->sortable()
                        ->words(5)
                        ->getStateUsing(fn ($record) => $record->vehicle_type == 1 ? __('messages.ambulance.contractual') : __('messages.ambulance.owned'))
                        ->searchable(),
                    ToggleColumn::make('is_available')
                        ->label(__('messages.ambulance.is_available'))
                        ->sortable()
                        ->afterStateUpdated(function () {
                            Notification::make()
                                ->title(__('messages.flash.ambulance_update'))
                                ->success()
                                ->send();
                        })
                        ->searchable(),

                ])
                ->filters([
                    SelectFilter::make('is_available')
                        ->label(__('messages.ambulance.is_available'))
                        ->options([
                            '' => __('messages.filter.all'),
                            1 => __('messages.bed.available'),
                            0 => __('messages.bed.not_available'),
                        ])->native(false),
                ])
                ->actionsColumnLabel(__('messages.common.action'))
                ->recordUrl(null)
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton()->action(function ($record) {
                        if (! canAccessRecord($record, $record->id)) {
                            // Flash::error(__('messages.flash.not_allow_access_record'));
                            Notification::make()
                                ->title(__('messages.flash.not_allow_access_record'))
                                ->danger()
                                ->send();

                            return Redirect::back();
                        }
                    }),
                    Tables\Actions\DeleteAction::make()->iconButton()->action(function ($record) {
                        if (! canAccessRecord($record, $record->id)) {

                            Notification::make()
                                ->title(__('messages.flash.ambulance_not_found'))
                                ->danger()
                                ->send();
                        }

                        $ambulanceCallModel = [AmbulanceCall::class];
                        $result = canDelete($ambulanceCallModel, 'ambulance_id', $record->id);
                        if ($result) {
                            return Notification::make()
                                ->title(__('messages.flash.ambulance_cant_delete'))
                                ->danger()
                                ->send();
                        }

                        $record->delete($record->id);

                        return Notification::make()
                            ->title(__('messages.flash.ambulance_delete'))
                            ->success()
                            ->send();
                    }),
                ])
                ->bulkActions([
                    // Tables\Actions\BulkActionGroup::make([
                    //     Tables\Actions\DeleteBulkAction::make(),
                    // ]),
                ])
                ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAmbulances::route('/'),
            'create' => Pages\CreateAmbulance::route('/create'),
            'view' => Pages\ViewAmbulance::route('/{record}'),
            'edit' => Pages\EditAmbulance::route('/{record}/edit'),
        ];
    }
}
