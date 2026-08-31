<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources;

use App\Filament\HospitalAdmin\Clusters\Services;
use App\Filament\HospitalAdmin\Clusters\Services\Resources\InsuranceResource\Pages;
use App\Models\Insurance;
use App\Models\PatientAdmission;
use App\Repositories\InsuranceRepository;
use Exception;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
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

class InsuranceResource extends Resource
{
    protected static ?string $model = Insurance::class;

    protected static ?string $cluster = Services::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('insurances')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('insurances')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.insurances');
    }

    public static function getLabel(): string
    {
        return __('messages.insurances');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Insurances')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Insurances')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Insurances')) {
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
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('messages.insurance.insurance').':')
                            ->validationMessages([
                                'unique' => __('messages.insurance.insurance').' '.__('messages.common.is_already_exists'),
                            ])
                            ->placeholder(__('messages.insurance.insurance'))
                            ->required()
                            ->validationAttribute(__('messages.insurance.insurance'))
                            ->maxLength(255),

                        TextInput::make('service_tax')
                            ->label(__('messages.insurance.service_tax').':')
                            ->placeholder(__('messages.insurance.service_tax'))
                            ->numeric()
                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[e\+\-]/gi, '')"])
                            ->required()
                            ->validationAttribute(__('messages.insurance.service_tax'))
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($get, $set, $state) {
                                if ($get('service_tax') == '' || empty($get('service_tax')) || ! is_string($get('service_tax'))) {
                                    $set('service_tax', 0);
                                }

                                self::getTotal($get, $set, $state);
                            }),

                        TextInput::make('discount')
                            ->label(__('messages.insurance.discount').': ('.__('messages.document.in_percentage').' (%))')
                            ->numeric()
                            ->placeholder(__('messages.insurance.discount'))
                            ->required()
                            ->validationAttribute(__('messages.insurance.discount'))
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($get, $set, $state) {
                                if ($state > 100) {
                                    $set('discount', 100);
                                }
                                if ($get('discount') == '' || empty($get('discount')) || ! is_string($get('discount'))) {
                                    $set('discount', 0);
                                }

                                self::getTotal($get, $set, $state);
                            })
                            ->maxLength(255),

                        TextInput::make('insurance_no')
                            ->label(__('messages.insurance.insurance_no').':')
                            ->placeholder(__('messages.insurance.insurance_no'))
                            ->required()
                            ->validationAttribute(__('messages.insurance.insurance_no'))
                            ->maxLength(255),

                        TextInput::make('insurance_code')
                            ->label(__('messages.insurance.insurance_code').':')
                            ->placeholder(__('messages.insurance.insurance_code'))
                            ->required()
                            ->validationAttribute(__('messages.insurance.insurance_code'))
                            ->maxLength(255),

                        TextInput::make('hospital_rate')
                            ->label(__('messages.insurance.hospital_rate').':')
                            ->placeholder(__('messages.insurance.hospital_rate'))
                            ->required()
                            ->validationAttribute(__('messages.insurance.hospital_rate'))
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($get, $set, $state) {
                                if ($get('hospital_rate') == '' || empty($get('hospital_rate')) || ! is_string($get('hospital_rate'))) {
                                    $set('hospital_rate', 0);
                                }
                                self::getTotal($get, $set, $state);
                            })
                            ->numeric()
                            ->minValue(1)
                            ->maxLength(255),

                        Textarea::make('remark')
                            ->rows(4)
                            ->label(__('messages.insurance.remark').':')
                            ->placeholder(__('messages.insurance.remark'))
                            ->validationAttribute(__('messages.insurance.remark'))
                            ->maxLength(255),

                        Toggle::make('status')
                            ->live()
                            ->label(__('messages.common.status'))
                            ->default(true),

                    ])->columns(2),

                Repeater::make('disease_details')
                    ->live()
                    ->label(__('messages.insurance.disease_details'))
                    ->addActionLabel(__('messages.common.add'))
                    ->schema([
                        TextInput::make('disease_name')
                            ->label(__('messages.insurance.diseases_name'))
                            ->placeholder(__('messages.insurance.diseases_name'))
                            ->required()
                            ->validationAttribute(__('messages.insurance.diseases_name'))
                            ->columns(1)
                            ->maxLength(255),
                        TextInput::make('disease_charge')
                            ->label(__('messages.insurance.diseases_charge'))
                            ->placeholder(__('messages.insurance.diseases_charge'))
                            ->numeric()
                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[e\+\-]/gi, '')"])
                            ->live(debounce: 500)
                            ->columns(1)
                            ->required()
                            ->validationAttribute(__('messages.insurance.diseases_charge')),
                    ])
                    ->afterStateUpdated(function ($get, $set, $state) {
                        self::getTotal($get, $set, $state);
                    })->columns(2)->columnSpanFull(),
                Grid::make('')->columns(6)->schema([
                    Grid::make('')->columns(1)->columnSpan(4),
                    Grid::make('Main')->schema([
                        TextInput::make('total')
                            ->live()
                            ->readOnly()
                            ->label(__('messages.insurance.total_amount').'('.getCurrencySymbol().')'.':')
                            ->inlineLabel(),
                    ])->columnSpan(2),
                ]),
            ]);
    }

    public static function getTotal($get, $set, $state): void
    {

        $items = collect($get('disease_details'))->values()->toArray();

        $total_amount = 0;

        foreach ($items as $item) {
            if (empty($item['disease_charge']) || $item['disease_charge'] == '' || ! is_numeric($item['disease_charge'])) {
                $item['disease_charge'] = 0;
            }
            $total_amount += $item['disease_charge'];
        }

        $total_amount = $get('hospital_rate') + $get('service_tax') + $total_amount;

        $total_amount = $total_amount - ($total_amount * $get('discount') / 100);
        $total_amount = is_numeric($total_amount) && $total_amount > 0 ? number_format($total_amount, 2, '.', '') : '0.00';
        $set('total', $total_amount);

        if (empty($total_amount) || $total_amount == '' || ! is_numeric($total_amount) || $total_amount == 0) {
            $set('total', 0);
        }
    }

    public static function table(Table $table): Table
    {
        if (! auth()->user()->hasRole(['Accountant', 'Doctor', 'Patient', 'Nurse', 'Pharmacist', 'Lab Technician', 'Case Manager']) && ! getModuleAccess('Insurances') && ! getModuleAccess('Packages') && ! getModuleAccess('Ambulances Calls') && ! getModuleAccess('Ambulances') && ! getModuleAccess('Services')) {
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
                    TextColumn::make('name')
                        ->label(__('messages.insurance.insurance'))
                        ->searchable()
                        ->sortable()
                        ->formatStateUsing(fn ($record) => '<a href="'.InsuranceResource::getUrl('view', ['record' => $record->id]).'" class="hoverLink">'.$record->name.'</a>')
                        ->html()
                        ->color('primary'),
                    TextColumn::make('insurance_no')
                        ->label(__('messages.insurance.insurance_no'))
                        ->searchable()
                        ->words(7)
                        ->sortable()
                        ->getStateUsing(fn ($record) => $record->insurance_no ?? __('messages.common.n/a')),
                    TextColumn::make('insurance_code')
                        ->label(__('messages.insurance.insurance_code'))
                        ->searchable()
                        ->sortable()
                        ->words(7)
                        ->getStateUsing(fn ($record) => $record->insurance_code ?? __('messages.common.n/a')),
                    TextColumn::make('service_tax')
                        ->label(__('messages.insurance.service_tax'))
                        ->searchable()
                        ->sortable()
                        ->getStateUsing(fn ($record) => getCurrencyFormat($record->service_tax) ?? __('messages.common.n/a')),
                    TextColumn::make('hospital_rate')
                        ->label(__('messages.insurance.hospital_rate'))
                        ->searchable()
                        ->sortable()
                        ->getStateUsing(fn ($record) => getCurrencyFormat($record->hospital_rate) ?? __('messages.common.n/a')),
                    TextColumn::make('total')
                        ->label(__('messages.common.total'))
                        ->searchable()
                        ->sortable()
                        ->getStateUsing(fn ($record) => getCurrencyFormat($record->total) ?? __('messages.common.n/a')),
                    ToggleColumn::make('status')
                        ->label(__('messages.common.status'))
                        ->afterStateUpdated(function () {
                            Notification::make()
                                ->title(__('messages.flash.insurance_updated'))
                                ->success()
                                ->send();
                        }),
                ])
                ->filters([
                    SelectFilter::make('status')
                        ->label(__('messages.common.status'))
                        ->options([
                            '' => __('messages.filter.all'),
                            '1' => __('messages.filter.active'),
                            '0' => __('messages.filter.deactive'),
                        ])->native(false),
                ])
                ->recordUrl(null)
                ->actionsColumnLabel(__('messages.common.action'))
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton(),
                    Tables\Actions\DeleteAction::make()->iconButton()->action(function ($record) {

                        if (! canAccessRecord(Insurance::class, $record->id)) {
                            Notification::make()
                                ->title(__('messages.flash.insurance_not_found'))
                                ->danger()
                                ->send();
                        }

                        $insuranceModel = [
                            PatientAdmission::class,
                        ];
                        $result = canDelete($insuranceModel, 'insurance_id', $record->id);
                        if ($result) {

                            return Notification::make()

                                ->title(__('messages.flash.insurance_cant_deleted'))
                                ->danger()
                                ->send();
                        }
                        try {
                            app(InsuranceRepository::class)->delete($record->id);

                            return Notification::make()

                                ->title(__('messages.flash.insurance_deleted'))
                                ->success()
                                ->send();
                        } catch (Exception $exception) {
                            return Notification::make()
                                ->title($exception->getMessage())
                                ->body($exception->getCode())
                                ->danger()
                                ->send();
                        }
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
            'index' => Pages\ListInsurances::route('/'),
            'create' => Pages\CreateInsurance::route('/create'),
            'view' => Pages\ViewInsurance::route('/{record}'),
            'edit' => Pages\EditInsurance::route('/{record}/edit'),
        ];
    }
}
