<?php

namespace App\Livewire;

use App\Models\Charge;
use App\Models\ChargeCategory;
use App\Models\IpdCharge;
use App\Models\IpdPatientDepartment;
use App\Repositories\IpdChargeRepository;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class IpdPatientChargeTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public $id;

    public $processedData = [];

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
    }

    public function CreateFormFields(): array
    {
        return [
            Group::make([
                Hidden::make('ipd_patient_department_id')->default($this->id),
                DatePicker::make('date')
                    ->label(__('messages.ipd_patient_charges.date'))
                    ->native(false)
                    ->default(now())
                    ->required(),
                Select::make('charge_type_id')
                    ->label(__('messages.ipd_patient_charges.charge_type_id').':')
                    ->options(fn () => getChargeTypeOptions())
                    ->required()
                    ->afterStateUpdated(function ($set, $get, $state) {
                        $charge_category_id = ChargeCategory::where('charge_type', $state)->whereTenantId(auth()->user()->tenant_id)->value('id');
                        if ($charge_category_id) {
                            $set('charge_category_id', $charge_category_id);
                            $charge_id = Charge::where('charge_category_id', $charge_category_id)->where('tenant_id', getLoggedInUser()->tenant_id)->value('id');
                            $set('charge_id', $charge_id);
                            $set('standard_charge', Charge::where('charge_category_id', $charge_category_id)->where('tenant_id', getLoggedInUser()->tenant_id)->value('standard_charge'));
                            $set('applied_charge', Charge::where('charge_category_id', $charge_category_id)->where('tenant_id', getLoggedInUser()->tenant_id)->value('standard_charge'));
                        }
                    })
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.ipd_patient_charges.charge_type_id').' '.__('messages.fields.required'),
                    ])
                    ->searchable()
                    ->preload()
                    ->live()
                    ->native(false),
                Select::make('charge_category_id')
                    ->live()
                    ->label(__('messages.pathology_test.charge_category').':')
                    ->placeholder(__('messages.pathology_category.select_charge_category'))
                    ->required()
                    ->options(fn ($get) => ChargeCategory::where('charge_type', $get('charge_type_id'))->where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                    ->afterStateUpdated(function ($set, $get) {
                        $id = $get('charge_category_id');
                        $charge_id = Charge::where('charge_category_id', $id)->where('tenant_id', getLoggedInUser()->tenant_id)->pluck('id')->first();
                        $set('charge_id', $charge_id);
                        if ($charge_id) {
                            $set('standard_charge', Charge::where('charge_category_id', $id)->where('tenant_id', getLoggedInUser()->tenant_id)->value('standard_charge'));
                        }
                    })
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.pathology_test.charge_category').' '.__('messages.fields.required'),
                    ])
                    ->disabled(function (callable $get) {
                        $id = $get('charge_type_id');
                        $charge_category_id = ChargeCategory::where('charge_type', $id)->whereTenantId(auth()->user()->tenant_id)->pluck('name', 'id');
                        if (! empty($charge_category_id->toArray())) {
                            return false;
                        }

                        return true;
                    })
                    ->searchable(),
                Select::make('charge_id')
                    ->live()
                    ->label(__('messages.delete.charge').':')
                    ->placeholder(__('messages.new_change.select_charge'))
                    ->options(function (callable $get) {
                        $id = $get('charge_category_id');

                        return Charge::where('charge_category_id', $id)->where('tenant_id', getLoggedInUser()->tenant_id)->pluck('code', 'id');
                    })
                    ->disabled(function (callable $get) {
                        $id = $get('charge_category_id');
                        $charge_id = Charge::where('charge_category_id', $id)->where('tenant_id', getLoggedInUser()->tenant_id)->pluck('code', 'id');
                        $charge_category_id = ChargeCategory::where('charge_type', $get('charge_type_id'))->whereTenantId(auth()->user()->tenant_id)->pluck('name', 'id');
                        if (! empty($charge_id->toArray()) || ! empty($charge_category_id->toArray())) {
                            return false;
                        }

                        return true;
                    })
                    ->native(false)
                    ->searchable()
                    ->afterStateUpdated(function ($set, $get, $state) {
                        $id = $get('charge_category_id');
                        $charge_id = Charge::where('charge_category_id', $id)->where('tenant_id', getLoggedInUser()->tenant_id)->where('id', $state)->value('standard_charge');
                        if ($id && $get('charge_id')) {
                            $set('standard_charge', $charge_id);
                        }
                    })
                    ->preload()
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.delete.charge').' '.__('messages.fields.required'),
                    ]),
                TextInput::make('standard_charge')
                    ->live()
                    ->required()
                    ->readOnly()
                    ->label(__('messages.radiology_test.standard_charge').':')
                    ->placeholder(__('messages.radiology_test.standard_charge'))
                    ->disabled(fn ($state) => $state == null ?? true),
                TextInput::make('applied_charge')
                    ->live()
                    ->label(function ($state) {
                        if ($state) {
                            return __('messages.ipd_patient_charges.applied_charge').' : '.'('.$state.')';
                        }

                        return __('messages.ipd_patient_charges.applied_charge').':';
                    })
                    ->placeholder(__('messages.ipd_patient_charges.applied_charge'))
                    ->disabled(fn ($state) => $state == null ?? true),
            ])->columns(2),
        ];
    }

    public function EditFormFields(): array
    {
        return [
            Group::make([
                Hidden::make('ipd_patient_department_id')->default($this->id),
                DatePicker::make('date')
                    ->label(__('messages.ipd_patient_charges.date'))
                    ->native(false)
                    ->required(),
                Select::make('charge_type_id')
                    ->label(__('messages.ipd_patient_charges.charge_type_id').':')
                    ->options(fn () => getChargeTypeOptions())
                    ->required()
                    ->afterStateUpdated(function ($set, $get, $state) {
                        $charge_category_id = ChargeCategory::where('charge_type', $state)->whereTenantId(auth()->user()->tenant_id)->value('id');
                        if ($charge_category_id) {
                            $set('charge_category_id', $charge_category_id);
                            $charge_id = Charge::where('charge_category_id', $charge_category_id)->where('tenant_id', getLoggedInUser()->tenant_id)->value('id');
                            $set('charge_id', $charge_id);
                            $set('standard_charge', Charge::where('charge_category_id', $charge_category_id)->where('tenant_id', getLoggedInUser()->tenant_id)->value('standard_charge'));
                            $set('applied_charge', Charge::where('charge_category_id', $charge_category_id)->where('tenant_id', getLoggedInUser()->tenant_id)->value('standard_charge'));
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.ipd_patient_charges.charge_type_id').' '.__('messages.fields.required'),
                    ])
                    ->native(false),
                Select::make('charge_category_id')
                    ->live()
                    ->label(__('messages.pathology_test.charge_category').':')
                    ->placeholder(__('messages.pathology_category.select_charge_category'))
                    ->required()
                    ->options(fn ($get) => ChargeCategory::where('charge_type', $get('charge_type_id'))->where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                    ->afterStateUpdated(function ($set, $get) {
                        $id = $get('charge_category_id');
                        $charge_id = Charge::where('charge_category_id', $id)->where('tenant_id', getLoggedInUser()->tenant_id)->pluck('id')->first();
                        $set('charge_id', $charge_id);
                        if ($charge_id) {
                            $set('standard_charge', Charge::where('charge_category_id', $id)->where('tenant_id', getLoggedInUser()->tenant_id)->value('standard_charge'));
                        }
                    })
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.pathology_test.charge_category').' '.__('messages.fields.required'),
                    ])
                    ->disabled(function (callable $get) {
                        $id = $get('charge_type_id');
                        $charge_category_id = ChargeCategory::where('charge_type', $id)->whereTenantId(auth()->user()->tenant_id)->pluck('name', 'id');
                        if (! empty($charge_category_id->toArray())) {
                            return false;
                        }

                        return true;
                    })
                    ->searchable(),
                Select::make('charge_id')
                    ->live()
                    ->label(__('messages.delete.charge').':')
                    ->placeholder(__('messages.new_change.select_charge'))
                    ->options(function (callable $get) {
                        $id = $get('charge_category_id');

                        return Charge::where('charge_category_id', $id)->where('tenant_id', getLoggedInUser()->tenant_id)->pluck('code', 'id');
                    })
                    ->disabled(function (callable $get) {
                        $id = $get('charge_category_id');
                        $charge_id = Charge::where('charge_category_id', $id)->where('tenant_id', getLoggedInUser()->tenant_id)->pluck('code', 'id');
                        $charge_category_id = ChargeCategory::where('charge_type', $get('charge_type_id'))->whereTenantId(auth()->user()->tenant_id)->pluck('name', 'id');
                        if (! empty($charge_id->toArray()) || ! empty($charge_category_id->toArray())) {
                            return false;
                        }

                        return true;
                    })
                    ->native(false)
                    ->searchable()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.delete.charge').' '.__('messages.fields.required'),
                    ])
                    ->afterStateUpdated(function ($set, $get, $state) {
                        $id = $get('charge_category_id');
                        $charge_id = Charge::where('charge_category_id', $id)->where('tenant_id', getLoggedInUser()->tenant_id)->where('id', $state)->value('standard_charge');
                        if ($id && $get('charge_id')) {
                            $set('standard_charge', $charge_id);
                        }
                    })
                    ->preload()
                    ->required(),
                TextInput::make('standard_charge')
                    ->live()
                    ->required()
                    ->readOnly()
                    ->label(__('messages.radiology_test.standard_charge').':')
                    ->placeholder(__('messages.radiology_test.standard_charge'))
                    ->disabled(fn ($state) => $state == null ?? true),
                TextInput::make('applied_charge')
                    ->live()
                    ->label(function ($state) {
                        if ($state) {
                            return __('messages.ipd_patient_charges.applied_charge').' : '.'('.$state.')';
                        }

                        return __('messages.ipd_patient_charges.applied_charge').':';
                    })
                    ->placeholder(__('messages.ipd_patient_charges.applied_charge'))
                    ->disabled(fn ($state) => $state == null ?? true),
            ])->columns(2),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Actions\CreateAction::make()
                    ->modalWidth('6xl')
                    ->model(IpdCharge::class)
                    ->createAnother(false)
                    ->form($this->CreateFormFields())
                    ->after(function ($record) {
                        app(IpdChargeRepository::class)->createNotification($record->toArray());
                    })
                    ->visible(fn () => ! IpdPatientDepartment::find($this->id)->bill_status && hasModulePermission('IPD Charges', 'create'))
                    ->modalWidth('6xl')
                    ->successNotificationTitle(__('messages.flash.charge_saved'))
                    ->modalHeading(__('messages.ipd_patient_charges.new_charge'))
                    ->label(__('messages.ipd_patient_charges.new_charge')),
            ])
            ->query(IpdCharge::where('ipd_patient_department_id', $this->id)->orderBy('id', 'desc'))
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('date')
                    ->label(__('messages.ipd_patient_charges.date'))
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('jS M, Y'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('charge_type_id')
                    ->label(__('messages.ipd_patient_charges.charge_type_id'))
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(fn ($record) => getChargeTypeName($record->charge_type_id))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chargecategory.name')
                    ->default(__('messages.common.n/a'))
                    ->label(__('messages.ipd_patient_charges.charge_category_id'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('charge_id')
                    ->default(__('messages.common.n/a'))
                    ->label(__('messages.ipd_patient_charges.charge_id'))
                    ->formatStateUsing(fn ($record) => $record->charge->code ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('standard_charge')
                    ->label(__('messages.ipd_patient_charges.standard_charge'))
                    ->formatStateUsing(function ($record) {
                        if (! empty($record->standard_charge)) {
                            return getCurrencyFormat($record->standard_charge);
                        } else {
                            return __('messages.common.n/a');
                        }
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('applied_charge')
                    ->label(__('messages.ipd_patient_charges.applied_charge'))
                    ->formatStateUsing(function ($record) {
                        if (! empty($record->applied_charge)) {
                            return getCurrencyFormat($record->applied_charge);
                        } else {
                            return __('messages.common.n/a');
                        }
                    })
                    ->searchable()
                    ->sortable(),
            ])
            // Actions
            ->actionsColumnLabel(__('messages.common.action'))
            ->actions([
                Actions\EditAction::make()
                    ->modalWidth('6xl')
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Charges', 'edit'))
                    ->form($this->EditFormFields())
                    ->successNotificationTitle(__('messages.flash.IPD_charge_updated')),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Charges', 'delete'))
                    ->successNotificationTitle(__('messages.flash.IPD_charge_deleted')),
            ])
            ->filters([
                //
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'))
            ->emptyStateDescription('');
    }

    public function render()
    {
        return view('livewire.ipd-patient-charge-table');
    }
}
