<?php

namespace App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources;

use App\Filament\HospitalAdmin\Clusters\HospitalCharge;
use App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources\DoctorOPDChargeResource\Pages;
use App\Models\DoctorOPDCharge;
use App\Models\User;
use App\Repositories\DoctorOPDChargeRepository;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DoctorOPDChargeResource extends Resource
{
    protected static ?string $model = DoctorOPDCharge::class;

    protected static ?string $cluster = HospitalCharge::class;

    protected static ?int $navigationSort = 3;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Doctor OPD Charges')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Doctor OPD Charges')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.doctor_opd_charges');
    }

    public static function getLabel(): string
    {
        return __('messages.doctor_opd_charges');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Doctor OPD Charges')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Doctor OPD Charges')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Doctor OPD Charges')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('doctor_id')
                    ->label(__('messages.doctor_opd_charge.doctor').':')
                    ->required()
                    ->options(fn () => app(DoctorOPDChargeRepository::class)->getDoctors())
                    ->native(false)
                    ->preload()
                    ->searchable()
                    ->unique('doctor_opd_charges', 'doctor_id', ignoreRecord: true)
                    ->placeholder(__('messages.web_home.select_doctor'))
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.doctor_opd_charge.doctor').' '.__('messages.fields.required'),
                    ]),
                Forms\Components\TextInput::make('standard_charge')
                    ->numeric()
                    ->minValue(1)
                    ->label(__('messages.doctor_opd_charge.standard_charge').':')
                    ->placeholder(__('messages.doctor_opd_charge.standard_charge'))
                    ->validationAttribute(__('messages.doctor_opd_charge.standard_charge'))
                    ->required(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Doctor OPD Charges')) {
            abort(404);
        }

        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(auth()->user()->tenant_id);

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    SpatieMediaLibraryImageColumn::make('doctor.user.profile')->collection(User::COLLECTION_PROFILE_PICTURES)->rounded()->label(__('messages.case.doctor'))->width(50)->height(50)
                        ->sortable(['first_name'])
                        ->defaultImageUrl(function ($record) {
                            if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                                return getUserImageInitial($record->id, $record->doctor->user->first_name);
                            }
                        }),
                    TextColumn::make('doctor.user.full_name')
                        ->label('')
                        ->formatStateUsing(function ($state, $record) {
                            return '<span class="font-bold">'.$record->doctor->user->full_name.'</span>';
                        })
                        ->html()
                        ->weight(FontWeight::SemiBold)
                        ->color('primary')
                        ->description(function ($record) {
                            return $record->doctor->user->email;
                        })
                        ->searchable(['first_name', 'last_name', 'email']),
                    TextColumn::make('standard_charge')
                        ->label(__('messages.doctor_opd_charge.standard_charge'))
                        ->searchable()
                        ->getStateUsing(fn ($record) => getCurrencyFormat($record->standard_charge) ?? __('messages.common.n/a'))
                        ->sortable(),
                ])
                ->filters([
                    //
                ])
                ->actionsColumnLabel(__('messages.common.action'))
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.OPD_charge_updated')),
                    Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.OPD_charge_deleted')),
                ])
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
            'index' => Pages\ManageDoctorOPDCharges::route('/'),
        ];
    }
}
