<?php

namespace App\Livewire;

use App\Models\IpdPatientDepartment;
use App\Models\IpdPrescription;
use App\Models\IpdPrescriptionItem;
use App\Models\Medicine;
use App\Models\MedicineBill;
use App\Models\Prescription;
use App\Models\SaleMedicine;
use App\Repositories\IpdPatientDepartmentRepository;
use App\Repositories\IpdPrescriptionRepository;
use Carbon\Carbon;
use Closure;
use Exception;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class IpdPatientPrescriptionTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public $id;

    public $ipdPrescriptionId;

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
        $this->ipdPrescriptionId;
    }

    public function GetRecord()
    {
        $ipdPatients = IpdPrescription::whereIpdPatientDepartmentId($this->id)->orderBy('id', 'desc');

        return $ipdPatients;
    }

    public function getFormFields(): array
    {
        return [
            Hidden::make('ipd_patient_department_id')->default($this->id),
            Textarea::make('header_note')
                ->rows(4)
                ->placeholder(__('messages.ipd_patient_prescription.header_note').':')
                ->label(__('messages.ipd_patient_prescription.header_note')),
            Repeater::make('prescription')
                ->schema([
                    Select::make('category_id')
                        ->label(__('messages.medicine.medicine_category').':')
                        ->placeholder(__('messages.medicine_bills.select_medicine'))
                        ->options(app(IpdPatientDepartmentRepository::class)->getMedicineCategoriesList())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->native(false)
                        ->required()
                        ->validationMessages([
                            'required' => __('messages.fields.the').' '.__('messages.medicine.medicine_category').' '.__('messages.fields.required'),
                        ])
                        ->columnSpan(3),
                    Select::make('medicine_id')
                        ->label(__('messages.medicine.medicine_category').':')
                        ->placeholder(__('messages.medicine_bills.select_medicine'))
                        ->options(fn ($get) => Medicine::where('tenant_id', getLoggedInUser()->tenant_id)->where('category_id', '=', $get('category_id'))->pluck('name', 'id')->toArray())
                        ->disabled(function ($get) {
                            if (! empty(Medicine::where('tenant_id', getLoggedInUser()->tenant_id)->where('category_id', '=', $get('category_id'))->get()->toArray())) {
                                return false;
                            }

                            return true;
                        })
                        ->live()
                        ->helperText(function ($state) {
                            $qty = Medicine::whereId($state)->where('tenant_id', getLoggedInUser()->tenant_id)->value('available_quantity');
                            if (isset($qty) && $qty > 10) {
                                return new HtmlString('<span style="color:#4BB543;">'.__('messages.item.available_quantity').' : '.$qty.'</span>');
                            } elseif (isset($qty) && $qty <= 10) {
                                return new HtmlString('<span style="color:red;">'.__('messages.item.available_quantity').' : '.$qty.'</span>');
                            }

                            return null;
                        })
                        ->rules([
                            fn (): Closure => function (string $attribute, $value, Closure $fail, $sta) {
                                if (Medicine::whereId($value)->where('tenant_id', getLoggedInUser()->tenant_id)->value('available_quantity') <= 0) {
                                    $fail('');
                                    Notification::make()->danger()->title(__('messages.medicine_bills.available_quantity').' '.Medicine::whereId($value)->where('tenant_id', getLoggedInUser()->tenant_id)->value('name').' '.__('messages.medicine_bills.is').' '.Medicine::whereId($value)->where('tenant_id', getLoggedInUser()->tenant_id)->value('available_quantity'))->send();
                                }
                            },
                        ])
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required()
                        ->columnSpan(3)
                        ->validationMessages([
                            'required' => __('messages.fields.the').' '.__('messages.medicine.medicine_category').' '.__('messages.fields.required'),
                        ]),
                    TextInput::make('dosage')
                        ->label(__('messages.ipd_patient_prescription.dosage').':')
                        ->placeholder(__('messages.ipd_patient_prescription.dosage').':')
                        ->columnSpan(1)
                        ->maxLength(255)
                        ->required(),
                    Select::make('day')
                        ->options(Prescription::DOSE_DURATION)
                        ->label(__('messages.prescription.duration').':')
                        ->live()
                        ->default(1)
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required()
                        ->columnSpan(3)
                        ->validationMessages([
                            'required' => __('messages.fields.the').' '.__('messages.prescription.duration').' '.__('messages.fields.required'),
                        ]),
                    Select::make('time')
                        ->options(Prescription::MEAL_ARR)
                        ->label(__('messages.prescription.time').':')
                        ->live()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->default(1)
                        ->required()
                        ->columnSpan(3)
                        ->validationMessages([
                            'required' => __('messages.fields.the').' '.__('messages.prescription.time').' '.__('messages.fields.required'),
                        ]),
                    Select::make('dose_interval')
                        ->options(Prescription::DOSE_INTERVAL)
                        ->label(__('messages.medicine_bills.dose_interval').':')
                        ->live()
                        ->searchable()
                        ->default(1)
                        ->preload()
                        ->native(false)
                        ->required()
                        ->columnSpan(3)
                        ->validationMessages([
                            'required' => __('messages.fields.the').' '.__('messages.medicine_bills.dose_interval').' '.__('messages.fields.required'),
                        ]),
                    Textarea::make('instruction')
                        ->rows(1)
                        ->columnSpan(2)
                        ->required()
                        ->maxLength(255)
                        ->placeholder(__('messages.ipd_patient_prescription.instruction').':')
                        ->label(__('messages.ipd_patient_prescription.instruction')),
                ])->columns(18)
                ->addActionLabel(__('messages.common.add'))
                ->live()
                ->rules([
                    fn (): Closure => function ($attribute, $value, $fail) {
                        $medicineIds = array_column($value, 'medicine_id');
                        if (count($medicineIds) !== count(array_unique($medicineIds))) {
                            $fail('');
                            Notification::make()->danger()->title(__('messages.medicine_bills.duplicate_medicine'))->send();
                        }
                    },
                ])
                ->deletable(function ($state) {
                    if (count($state) === 1) {
                        return false;
                    }

                    return true;
                }),
            Textarea::make('footer_note')
                ->rows(4)
                ->placeholder(__('messages.ipd_patient_prescription.footer_note').':')
                ->label(__('messages.ipd_patient_prescription.footer_note')),
        ];
    }

    public function getEditFormFields(): array
    {
        return [
            Hidden::make('ipd_patient_department_id')->default($this->id),
            Textarea::make('header_note')
                ->rows(4)
                ->placeholder(__('messages.ipd_patient_prescription.header_note').':')
                ->label(__('messages.ipd_patient_prescription.header_note')),
            Repeater::make('prescription')
                ->schema([
                    Select::make('category_id')
                        ->label(__('messages.medicine.medicine_category').':')
                        ->placeholder(__('messages.medicine_bills.select_medicine'))
                        ->options(app(IpdPatientDepartmentRepository::class)->getMedicineCategoriesList())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->native(false)
                        ->required()
                        ->columnSpan(3)
                        ->validationMessages([
                            'required' => __('messages.fields.the').' '.__('messages.medicine.medicine_category').' '.__('messages.fields.required'),
                        ]),
                    Select::make('medicine_id')
                        ->label(__('messages.medicine.medicine_category').':')
                        ->placeholder(__('messages.medicine_bills.select_medicine'))
                        ->options(fn ($get) => Medicine::where('tenant_id', getLoggedInUser()->tenant_id)->where('category_id', '=', $get('category_id'))->pluck('name', 'id')->toArray())
                        ->disabled(function ($get) {
                            if (! empty(Medicine::where('tenant_id', getLoggedInUser()->tenant_id)->where('category_id', '=', $get('category_id'))->get()->toArray())) {
                                return false;
                            }

                            return true;
                        })
                        ->live()
                        ->helperText(function ($state) {
                            $qty = Medicine::whereId($state)->where('tenant_id', getLoggedInUser()->tenant_id)->value('available_quantity');
                            if (isset($qty) && $qty > 10) {
                                return new HtmlString('<span style="color:#4BB543;">'.__('messages.item.available_quantity').' : '.$qty.'</span>');
                            } elseif (isset($qty) && $qty <= 10) {
                                return new HtmlString('<span style="color:red;">'.__('messages.item.available_quantity').' : '.$qty.'</span>');
                            }

                            return null;
                        })
                        ->rules([
                            fn (): Closure => function (string $attribute, $value, Closure $fail, $sta) {
                                if (Medicine::whereId($value)->where('tenant_id', getLoggedInUser()->tenant_id)->value('available_quantity') <= 0) {
                                    $fail('');
                                    Notification::make()->danger()->title(__('messages.medicine_bills.available_quantity').' '.Medicine::whereId($value)->where('tenant_id', getLoggedInUser()->tenant_id)->value('name').' '.__('messages.medicine_bills.is').' '.Medicine::whereId($value)->where('tenant_id', getLoggedInUser()->tenant_id)->value('available_quantity'))->send();
                                }
                            },
                        ])
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required()
                        ->columnSpan(3)
                        ->validationMessages([
                            'required' => __('messages.fields.the').' '.__('messages.medicine.medicine_category').' '.__('messages.fields.required'),
                        ]),
                    TextInput::make('dosage')
                        ->label(__('messages.ipd_patient_prescription.dosage').':')
                        ->columnSpan(1)
                        ->maxLength(255)
                        ->required(),
                    Select::make('day')
                        ->options(Prescription::DOSE_DURATION)
                        ->label(__('messages.prescription.duration').':')
                        ->live()
                        ->default(1)
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required()
                        ->columnSpan(3)
                        ->validationMessages([
                            'required' => __('messages.fields.the').' '.__('messages.prescription.duration').' '.__('messages.fields.required'),
                        ]),
                    Select::make('time')
                        ->options(Prescription::MEAL_ARR)
                        ->label(__('messages.prescription.time').':')
                        ->live()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->default(1)
                        ->required()
                        ->columnSpan(3)
                        ->validationMessages([
                            'required' => __('messages.fields.the').' '.__('messages.prescription.time').' '.__('messages.fields.required'),
                        ]),
                    Select::make('dose_interval')
                        ->options(Prescription::DOSE_INTERVAL)
                        ->label(__('messages.medicine_bills.dose_interval').':')
                        ->live()
                        ->searchable()
                        ->default(1)
                        ->preload()
                        ->native(false)
                        ->required()
                        ->columnSpan(3)
                        ->validationMessages([
                            'required' => __('messages.fields.the').' '.__('messages.medicine_bills.dose_interval').' '.__('messages.fields.required'),
                        ]),
                    Textarea::make('instruction')
                        ->rows(1)
                        ->columnSpan(2)
                        ->required()
                        ->maxLength(255)
                        ->label(__('messages.ipd_patient_prescription.instruction')),
                ])->columns(18)
                ->addActionLabel(__('messages.common.add'))
                ->live()
                ->rules([
                    fn (): Closure => function ($attribute, $value, $fail) {
                        $medicineIds = array_column($value, 'medicine_id');
                        if (count($medicineIds) !== count(array_unique($medicineIds))) {
                            $fail('');
                            Notification::make()->danger()->title(__('messages.medicine_bills.duplicate_medicine'))->send();
                        }
                    },
                ])
                ->deletable(function ($state) {
                    if (count($state) === 1) {
                        return false;
                    }

                    return true;
                }),
            Textarea::make('footer_note')
                ->rows(4)
                ->placeholder(__('messages.ipd_patient_prescription.footer_note').':')
                ->label(__('messages.ipd_patient_prescription.footer_note')),
        ];
    }

    public function getTableColumns(): array
    {
        return [
            TextColumn::make('id')
                ->searchable()
                ->formatStateUsing(fn ($record) => \Carbon\Carbon::parse($record->created_at)->translatedFormat('jS M, Y'))
                ->sortable()
                ->label(__('messages.common.created_on')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50])
            ->headerActions([
                Actions\CreateAction::make()
                    ->modalWidth('7xl')
                    ->createAnother(false)
                    ->visible(fn () => hasModulePermission('IPD Prescriptions', 'create'))
                    ->form($this->getFormFields())
                    ->using(function (array $data, string $model) {
                        try {
                            $this->ipdPrescriptionId = $model::create(Arr::except($data, ['prescription']));

                            return $this->ipdPrescriptionId;
                        } catch (Exception $e) {

                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                        }
                    })
                    ->after(function (array $data) {
                        if ($this->ipdPrescriptionId->id) {

                            $transformedData = array_merge($data, [
                                'category_id' => array_column($data['prescription'], 'category_id'),
                                'medicine_id' => array_column($data['prescription'], 'medicine_id'),
                                'dosage' => array_column($data['prescription'], 'dosage'),
                                'day' => array_column($data['prescription'], 'day'),
                                'time' => array_column($data['prescription'], 'time'),
                                'dose_interval' => array_column($data['prescription'], 'dose_interval'),
                                'instruction' => array_column($data['prescription'], 'instruction'),
                            ]);

                            $data = Arr::except($transformedData, ['prescription']);

                            $ipdDepartment = IpdPatientDepartment::with('patient', 'doctor')->whereId($data['ipd_patient_department_id'])->first();
                            $amount = 0;
                            $qty = 0;
                            $medicineBill = MedicineBill::create([
                                'bill_number' => 'BIL'.generateUniqueBillNumber(),
                                'patient_id' => $ipdDepartment->patient->id,
                                'doctor_id' => $ipdDepartment->doctor->id,
                                'model_type' => \App\Models\IpdPrescription::class,
                                'model_id' => $this->ipdPrescriptionId->id,
                                'payment_status' => MedicineBill::UNPAID,
                                'discount' => 0,
                                'net_amount' => 0,
                                'total' => 0,
                                'tax_amount' => 0,
                                'payment_type' => 0,
                                'bill_date' => Carbon::now(),
                            ]);

                            foreach ($data['category_id'] as $key => $value) {
                                $ipdPrescriptionItem = [
                                    'ipd_prescription_id' => $this->ipdPrescriptionId->id,
                                    'category_id' => $data['category_id'][$key],
                                    'medicine_id' => $data['medicine_id'][$key],
                                    'dosage' => $data['dosage'][$key],
                                    'day' => $data['day'][$key],
                                    'time' => $data['time'][$key],
                                    'dose_interval' => $data['dose_interval'][$key],
                                    'instruction' => $data['instruction'][$key],
                                ];

                                $ipdPrescriptionItem = IpdPrescriptionItem::create($ipdPrescriptionItem);

                                $medicine = Medicine::find($data['medicine_id'][$key]);
                                $itemAmount = $data['day'][$key] * $data['dose_interval'][$key] * $medicine->selling_price;
                                $amount += $itemAmount;
                                $qty = $data['day'][$key] * $data['dose_interval'][$key];

                                $saleMedicineArray = [
                                    'medicine_bill_id' => $medicineBill->id,
                                    'medicine_id' => $medicine->id,
                                    'sale_quantity' => $qty,
                                    'sale_price' => $medicine->selling_price,
                                    'expiry_date' => date('Y-m-d h:i', 0000 - 00 - 00),
                                    'amount' => $amount,
                                    'tax' => 0,
                                ];

                                $saleMedicine = SaleMedicine::create($saleMedicineArray);
                            }

                            app(IpdPrescriptionRepository::class)->createNotification($data);
                            $medicineBill->update([
                                'net_amount' => $amount,
                                'total' => $amount,
                            ]);
                        } else {
                            Notification::make()
                                ->danger()
                                ->title(function (Exception $e) {
                                    return $e->getMessage();
                                })
                                ->send();
                        }
                    })
                    ->successNotificationTitle(__('messages.flash.IPD_Prescription_saved'))
                    ->modalHeading(__('messages.ipd_patient_prescription.new_prescription'))
                    ->label(__('messages.ipd_patient_prescription.new_prescription')),
            ])
            ->query(self::GetRecord())
            ->columns($this->getTableColumns())
            ->actions([
                Actions\EditAction::make()
                    ->modalWidth('7xl')
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Prescriptions', 'edit'))
                    ->mutateRecordDataUsing(function (Model $record, array $data): array {
                        $transformedData = IpdPrescriptionItem::where('ipd_prescription_id', $record->id)->get()->toArray();

                        $data['prescription'] = $transformedData;

                        return $data;
                    })
                    ->using(function (Model $record, array $data): Model {
                        try {
                            $record->update(Arr::except($data, ['prescription']));

                            return $record;
                        } catch (Exception $e) {

                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                        }
                    })
                    ->after(function (Model $record, array $data) {
                        $input = $data;
                        if ($record->id) {
                            $medicineBill = MedicineBill::whereModelId($record->id)->whereModelType(\App\Models\IpdPrescription::class)->first();
                            $medicineBill->saleMedicine()->delete();
                            $record->ipdPrescriptionItems()->delete();
                            $ipdDepartment = IpdPatientDepartment::with('patient', 'doctor')->whereId($input['ipd_patient_department_id'])->first();
                            $amount = 0;
                            $qty = 0;

                            $transformedData = array_merge($data, [
                                'category_id' => array_column($data['prescription'], 'category_id'),
                                'medicine_id' => array_column($data['prescription'], 'medicine_id'),
                                'dosage' => array_column($data['prescription'], 'dosage'),
                                'day' => array_column($data['prescription'], 'day'),
                                'time' => array_column($data['prescription'], 'time'),
                                'dose_interval' => array_column($data['prescription'], 'dose_interval'),
                                'instruction' => array_column($data['prescription'], 'instruction'),
                            ]);

                            $input = Arr::except($transformedData, ['prescription']);

                            foreach ($input['category_id'] as $key => $value) {
                                $ipdPrescriptionItem = [
                                    'ipd_prescription_id' => $record->id,
                                    'category_id' => $input['category_id'][$key],
                                    'medicine_id' => $input['medicine_id'][$key],
                                    'dosage' => $input['dosage'][$key],
                                    'day' => $input['day'][$key],
                                    'time' => $input['time'][$key],
                                    'dose_interval' => $input['dose_interval'][$key],
                                    'instruction' => $input['instruction'][$key],
                                ];
                                IpdPrescriptionItem::create($ipdPrescriptionItem);

                                $medicine = Medicine::find($input['medicine_id'][$key]);
                                $amount += $input['day'][$key] * $input['dose_interval'][$key] * $medicine->selling_price;
                                $qty = $input['day'][$key] * $input['dose_interval'][$key];
                                $saleMedicineArray = [
                                    'medicine_bill_id' => $medicineBill->id,
                                    'medicine_id' => $medicine->id,
                                    'sale_quantity' => $qty,
                                    'sale_price' => $medicine->selling_price,
                                    'expiry_date' => date('Y-m-d h:i', 0000 - 00 - 00),
                                    'amount' => $amount,
                                    'tax' => 0,
                                ];
                                SaleMedicine::create($saleMedicineArray);
                            }
                            $medicineBill->update([
                                'net_amount' => $amount,
                                'total' => $amount,
                            ]);
                        } else {
                            Notification::make()
                                ->danger()
                                ->title(function (Exception $e) {
                                    return $e->getMessage();
                                })
                                ->send();
                        }
                    })
                    ->form($this->getEditFormFields())
                    ->successNotificationTitle(__('messages.flash.IPD_Prescription_updated')),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Prescriptions', 'delete'))
                    ->using(function (Model $record) {
                        try {
                            if (! canAccessRecord($record, $record->id)) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('messages.flash.ipd_prescription_not_found'))
                                    ->send();
                            }
                            $record->ipdPrescriptionItems()->delete();
                            $record->delete();
                        } catch (Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title($e->getMessage())
                                ->send();
                        }
                    })
                    ->successNotificationTitle(__('messages.flash.IPD_prescription_deleted')),
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
        return view('livewire.ipd-patient-prescription-table');
    }
}
