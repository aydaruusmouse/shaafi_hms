<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources;

use App\Filament\HospitalAdmin\Clusters\Billings;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Bill;
use App\Models\PatientAdmission;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 7;

    protected static ?string $cluster = Billings::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Bills')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Bills')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.bills');
    }

    public static function getLabel(): string
    {
        return __('messages.bills');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist', 'Accountant']) && getModuleAccess('Bills')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist', 'Accountant']) && getModuleAccess('Bills')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist', 'Accountant']) && getModuleAccess('Bills')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist', 'Patient', 'Accountant'])) {
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
                        Forms\Components\Select::make('patient_admission_id')
                            ->label(__('messages.bill.admission_id'))
                            ->options(function () {
                                return app()->call('App\\Repositories\\BillRepository@getPatientAdmissionIdList', ['isEditScreen' => false]);
                            })
                            ->placeholder(__('messages.document.select_admission_id'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.bill.admission_id').' '.__('messages.fields.required'),
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $patientAdmission = PatientAdmission::with(['patient.patientUser', 'doctor.doctorUser', 'package.packageServicesItems.service'])->where('patient_admission_id', $state)->first();
                                $admissionDate = Carbon::parse($patientAdmission->admission_date);
                                $dischargeDate = Carbon::parse($patientAdmission->discharge_date);
                                $set('email', $patientAdmission->patient->patientUser->email ?? __('messages.common.n/a'));
                                $set('phone', $patientAdmission->patient->patientUser->phone ?? __('messages.common.n/a'));
                                $set('gender', $patientAdmission->patient->patientUser->gender ?? __('messages.common.n/a'));
                                $set('dob', $patientAdmission->patient->patientUser->dob ?? __('messages.common.n/a'));
                                $set('doctor_id', $patientAdmission->doctor->doctorUser->full_name ?? __('messages.common.n/a'));
                                $set('admission_date', $patientAdmission->admission_date ?? __('messages.common.n/a'));
                                $set('discharge_date', $patientAdmission->discharge_date ?? __('messages.common.n/a'));
                                $set('package_id', $patientAdmission->package->name ?? __('messages.common.n/a'));
                                $set('insurance_id', $patientAdmission->insurance->name ?? __('messages.common.n/a'));
                                $set('total_days', max(0, round($admissionDate->diffInDays($dischargeDate)) + 1) ?? __('messages.common.n/a'));
                                $set('policy_no', $patientAdmission->policy_no ?? __('messages.common.n/a'));
                                $set('patient_id', $patientAdmission->patient_id ?? __('messages.common.n/a'));

                                if ($patientAdmission->package && $patientAdmission->package->packageServicesItems) {
                                    $billItems = [];

                                    foreach ($patientAdmission->package->packageServicesItems as $item) {

                                        $billItems[] = [
                                            'item_name' => $item->service->name ?? 'N/A',
                                            'qty' => $item->quantity ?? 1,
                                            'price' => $item->rate ?? 0,
                                            'amount' => $item->amount ?? 0,
                                        ];
                                    }

                                    $set('bill_items', $billItems);
                                    self::updateTotal($get, $set);
                                }
                            }),
                        Hidden::make('patient_id'),

                        Forms\Components\DatePicker::make('bill_date')
                            ->label(__('messages.bill.bill_date'))
                            ->required()
                            ->validationAttribute(__('messages.bill.bill_date'))
                            ->native(false)
                            ->default(now()),

                        Forms\Components\TextInput::make('email')
                            ->label(__('messages.bill.patient_email'))
                            ->readonly()
                            ->placeholder(__('messages.bill.patient_email')),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('messages.bill.patient_cell_no'))
                            ->readonly()
                            ->placeholder(__('messages.bill.patient_cell_no')),

                        Forms\Components\Radio::make('gender')
                            ->label(__('messages.bill.patient_gender'))
                            ->options([
                                '0' => __('messages.user.male'),
                                '1' => __('messages.user.female'),
                            ])
                            ->inline(),

                        Forms\Components\TextInput::make('dob')
                            ->label(__('messages.bill.patient_dob'))
                            ->readonly()
                            ->placeholder(__('messages.bill.patient_dob')),

                        Forms\Components\TextInput::make('doctor_id')
                            ->label(__('messages.case.doctor'))
                            ->readonly()
                            ->placeholder(__('messages.case.doctor')),

                        Forms\Components\TextInput::make('admission_date')
                            ->label(__('messages.bill.admission_date'))
                            ->readonly()
                            ->placeholder(__('messages.bill.admission_date')),

                        Forms\Components\TextInput::make('discharge_date')
                            ->label(__('messages.bill.discharge_date'))
                            ->readonly()
                            ->placeholder(__('messages.bill.discharge_date')),

                        Forms\Components\TextInput::make('package_id')
                            ->label(__('messages.bill.package_name'))
                            ->readonly()
                            ->placeholder(__('messages.bill.package_name')),

                        Forms\Components\TextInput::make('insurance_id')
                            ->label(__('messages.bill.insurance_name'))
                            ->readonly()
                            ->placeholder(__('messages.bill.insurance_name')),

                        Forms\Components\TextInput::make('total_days')
                            ->label(__('messages.bill.total_days'))
                            ->readonly()
                            ->minValue(0)
                            ->placeholder(__('messages.bill.total_days')),

                        Forms\Components\TextInput::make('policy_no')
                            ->label(__('messages.bill.policy_no'))
                            ->readonly()
                            ->placeholder(__('messages.bill.policy_no')),

                        Forms\Components\Grid::make('full')
                            ->schema([
                                static::getItemsRepeater(),
                            ])->columnSpan('full'),
                        Forms\Components\TextInput::make('total_amt')
                            ->label(__('messages.bill.amount'))
                            ->readonly()
                            ->reactive()
                            ->live()
                            ->afterStateUpdated(function ($get, $set) {
                                self::updateTotal($get, $set);
                            }),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Accountant', 'Receptionist']) && ! getModuleAccess('Bills')) {
            abort(404);
        }
        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('tenant_id', getLoggedInUser()->tenant_id);
            $user = Auth::user();
            if ($user->hasRole('Patient')) {
                $query->where('patient_id', $user->owner_id);
            }

            return $query;
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('bill_id')
                    ->searchable()
                    ->label(__('messages.bill.bill_id'))
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record): string => route('filament.hospitalAdmin.billings.resources.bills.view', $record)),
                ViewColumn::make('manualPayment.payment_type')
                    ->label(__('messages.subscription_plans.payment_method'))
                    ->hidden(! auth()->user()->hasRole('Patient'))
                    ->view('tables.columns.hospitalAdmin.payment_type'),
                SpatieMediaLibraryImageColumn::make('patient.user.profile')
                    ->label(__('messages.invoice.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
                    ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.user.full_name')
                    ->label('')
                    ->description(function (Bill $record) {
                        return $record->patient->user->email;
                    })
                    ->html()
                    ->sortable(['first_name'])
                    ->formatStateUsing(fn ($state, $record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'"class="hoverLink">'.$state.'</a>')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(['first_name', 'last_name', 'email']),
                Tables\Columns\TextColumn::make('status')
                    ->getStateUsing(function ($record) {
                        if ($record->status == 0 || empty($record->status)) {
                            return __('messages.employee_payroll.unpaid');
                        } elseif ($record->status == 2) {
                            return __('messages.appointment.pending');
                        } else {
                            return __('messages.invoice.paid');
                        }
                    })
                    ->color(function ($record) {
                        if ($record->status == 0 || empty($record->status)) {
                            return 'danger';
                        } elseif ($record->status == 2) {
                            return 'info';
                        } else {
                            return 'success';
                        }
                    })
                    ->badge()
                    ->label(__('messages.common.status')),
                Tables\Columns\TextColumn::make('bill_date')
                    ->view('tables.columns.hospitalAdmin.bill-date')
                    ->label(__('messages.bill.bill_date'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('messages.bill.amount'))
                    ->formatStateUsing(function (Bill $record) {
                        return getCurrencyFormat($record->amount);
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->actions([
                TableAction::make('pdf')
                    ->iconButton()
                    ->hidden(auth()->user()->hasRole('Patient'))
                    ->color('warning')
                    ->icon('heroicon-s-printer')
                    ->url(function ($record) {
                        return route('bills.pdf', $record->id);
                    })
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.bill_updated')),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.bill_deleted')),
            ])->actionsColumnLabel((auth()->user()->hasRole('Patient')) ? '' : __('messages.common.action'))
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
            'index' => Pages\ListBills::route('/'),
            'create' => Pages\CreateBill::route('/create'),
            'edit' => Pages\EditBill::route('/{record}/edit'),
            'view' => Pages\ViewBill::route('/{record}'),
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('bill_items')
            ->relationship('billItems')
            ->label(__('messages.invoice.add'))
            ->relationship('billItems')
            ->schema([
                Forms\Components\TextInput::make('item_name')
                    ->label(__('messages.bill.item_name'))
                    ->placeholder(__('messages.bill.item_name'))
                    ->required()
                    ->validationAttribute(__('messages.invoice.item_name'))
                    ->columnSpan('3'),

                Forms\Components\TextInput::make('qty')
                    ->label(__('messages.bill.qty'))
                    ->placeholder(__('messages.bill.qty'))
                    ->numeric()
                    ->required()
                    ->validationAttribute(__('messages.bill.qty'))
                    ->columnSpan('3')
                    ->reactive()
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $state < 0 ? $set('qty', 0) : $set('qty', $state);
                        $price = $get('price');
                        $set('amount', $state * $price);
                        self::updateTotal($get, $set);
                    }),

                Forms\Components\TextInput::make('price')
                    ->label(__('messages.bill.price'))
                    ->placeholder(__('messages.bill.price'))
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->validationAttribute(__('messages.bill.price'))
                    ->columnSpan('3')
                    ->reactive()
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $state < 0 ? $set('price', 0) : $set('price', $state);
                        $qty = $get('qty');
                        $set('amount', $state * $qty);
                        self::updateTotal($get, $set);
                    }),

                Forms\Components\TextInput::make('amount')
                    ->label(__('messages.bill.amount'))
                    ->numeric()
                    ->minValue(1)
                    ->readonly()
                    ->columnSpan('3')
                    ->reactive()
                    ->live(),
            ])->columns(12)
            ->columnSpan('full')
            ->dehydrated()
            ->afterStateUpdated(function ($get, $set) {
                self::updateTotal($get, $set);
            });
    }

    public static function updateTotal($get, $set): void
    {
        $items = collect($get('bill_items'))->values()->toArray();
        $subtotal = 0;

        foreach ($items as $item) {
            $price = isset($item['price']) && is_numeric($item['price']) ? (float) $item['price'] : 0;
            $qty = isset($item['qty']) && is_numeric($item['qty']) ? (int) $item['qty'] : 0;
            $subtotal += $price * $qty;
        }

        // Set total_amt with 2 decimal places
        $set('total_amt', number_format($subtotal, 2, '.', ''));
    }
}
