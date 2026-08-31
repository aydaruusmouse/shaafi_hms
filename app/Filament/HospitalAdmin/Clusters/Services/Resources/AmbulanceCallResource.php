<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Filament\HospitalAdmin\Clusters\Services;
use App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceCallResource\Pages;
use App\Models\Ambulance;
use App\Models\AmbulanceCall;
use App\Models\User;
use App\Repositories\PatientRepository;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redirect;

class AmbulanceCallResource extends Resource
{
    protected static ?string $model = AmbulanceCall::class;

    protected static ?string $cluster = Services::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Ambulances Calls')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Ambulances Calls')) {
            return false;
        }

        return true;
    }

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('messages.ambulance_call.ambulance_calls');
    }

    public static function getLabel(): string
    {
        return __('messages.ambulance_call.ambulance_calls');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist']) && getModuleAccess('Ambulances Calls')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist']) && getModuleAccess('Ambulances Calls')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist']) && getModuleAccess('Ambulances Calls')) {
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
                        Select::make('patient_id')
                            ->label(__('messages.ambulance_call.patient').':')
                            ->required()
                            ->placeholder(__('messages.document.select_patient'))
                            ->options(app(PatientRepository::class)->getPatients())
                            ->preload()
                            ->searchable()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.ambulance_call.patient').' '.__('messages.fields.required'),
                            ]),
                        Select::make('ambulance_id')
                            ->label(__('messages.ambulance.vehicle_model').':')
                            ->required()
                            ->placeholder(__('messages.ambulance_call.select_ambulance'))
                            ->options(fn ($record, $operation) => self::getAmbulances($record, $operation))
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn ($set, $state) => $set('driver_name', Ambulance::whereId($state)->where('tenant_id', getLoggedInUser()->tenant_id)->whereIsAvailable(1)->value('driver_name')))
                            ->searchable()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.ambulance.vehicle_model').' '.__('messages.fields.required'),
                            ]),
                        DatePicker::make('date')
                            ->label(__('messages.ambulance_call.date').':')
                            ->required()
                            ->validationAttribute(__('messages.ambulance_call.date'))
                            ->placeholder(__('messages.ambulance_call.date'))
                            ->native(false),
                        TextInput::make('driver_name')
                            ->readOnly()
                            ->required()
                            ->validationAttribute(__('messages.ambulance_call.driver_name'))
                            ->placeholder(__('messages.ambulance_call.driver_name'))
                            ->label(__('messages.ambulance_call.driver_name').':'),
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->validationAttribute(__('messages.ambulance_call.amount'))
                            ->placeholder(__('messages.ambulance_call.amount'))
                            ->label(__('messages.ambulance_call.amount').':'),
                    ])
                    ->columns(2),

            ]);
    }

    public static function getAmbulances($record, $operation)
    {
        if ($operation == 'create') {
            return Ambulance::where('tenant_id', getLoggedInUser()->tenant_id)->whereIsAvailable(1)->pluck('vehicle_model', 'id')->sort();
        } else {
            $availableAmbulances = Ambulance::where('id', $record->ambulance_id)
                ->where('tenant_id', getLoggedInUser()->tenant_id)
                ->pluck('vehicle_model', 'id');

            $allAvailableAmbulances = Ambulance::where('tenant_id', getLoggedInUser()->tenant_id)
                ->whereIsAvailable(1)
                ->pluck('vehicle_model', 'id');

            return $availableAmbulances->union($allAvailableAmbulances)->sort();
        }
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist']) && ! getModuleAccess('Ambulances Calls')) {
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
                    SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                        ->label(__('messages.ambulance_call.patient'))
                        ->circular()
                        ->defaultImageUrl(function ($record) {
                            if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                                return getUserImageInitial($record->id, $record->patient->user->full_name);
                            }
                        })
                        ->sortable(['first_name'])
                        ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                        ->collection('profile')
                        ->width(50)->height(50),
                    TextColumn::make('patient.patientUser.full_name')
                        ->label('')
                        ->html()
                        ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'" class="hoverLink">'.$record->patient->patientUser->full_name.'</a>')
                        ->color('primary')
                        ->weight(FontWeight::SemiBold)
                        ->description(fn ($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                        ->searchable(['users.first_name', 'users.last_name']),
                    TextColumn::make('ambulance.vehicle_model')
                        ->label(__('messages.ambulance_call.vehicle_model'))
                        ->sortable()
                        ->searchable(),
                    TextColumn::make('ambulance.driver_name')
                        ->label(__('messages.ambulance_call.driver_name'))
                        ->sortable()
                        ->searchable(),
                    TextColumn::make('date')
                        ->label(__('messages.ambulance_call.date'))
                        ->sortable()
                        ->badge()
                        ->getStateUsing(fn ($record) => $record->date ? \Carbon\Carbon::parse($record->date)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                        ->searchable(),
                    TextColumn::make('amount')
                        ->label(__('messages.ambulance_call.amount'))
                        ->sortable()
                        ->getStateUsing(fn ($record) => $record->amount ? getCurrencyFormat($record->amount) : __('messages.common.n/a')),
                ])
                ->filters([
                    //
                ])
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton()->action(function ($record) {
                        if (! canAccessRecord($record, $record->id)) {

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
                                ->title(__('messages.flash.not_allow_access_record'))
                                ->danger()
                                ->send();
                        }

                        $record->delete($record->id);

                        Notification::make()
                            ->title(__('messages.flash.service_deleted'))
                            ->success()
                            ->send();
                    }),
                ])
                ->actionsColumnLabel(__('messages.common.action'))
                ->recordUrl(null)
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
            'index' => Pages\ListAmbulanceCalls::route('/'),
            'create' => Pages\CreateAmbulanceCall::route('/create'),
            'view' => Pages\ViewAmbulanceCall::route('/{record}'),
            'edit' => Pages\EditAmbulanceCall::route('/{record}/edit'),
        ];
    }
}
