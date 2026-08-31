<?php

namespace App\Livewire;

use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Component;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class HospitalUserTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public function mount($record)
    {
        $this->record = $record;
    }

    public function GetRecord()
    {
        $query = User::where('id', '!=', $this->record->id)->where('tenant_id', $this->record->tenant_id);

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(self::GetRecord())
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('full_name')
                    ->default(__('messages.common.n/a'))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name'])
                    ->label(__('messages.users')),
                TextColumn::make('id')
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (User $record) {
                        foreach ($record->roles as $role) {
                            if ($role->name == 'Admin') {
                                return __('messages.role.admin');
                            } elseif ($role->name == 'Doctor') {
                                return __('messages.role.doctor');
                            } elseif ($role->name == 'Nurse') {
                                return __('messages.role.nurse');
                            } elseif ($role->name == 'Receptionist') {
                                return __('messages.role.receptionist');
                            } elseif ($role->name == 'Case Manager') {
                                return __('messages.role.case_manager');
                            } elseif ($role->name == 'Pharmacist') {
                                return __('messages.role.pharmacist');
                            } elseif ($role->name == 'Accountant') {
                                return __('messages.role.accountant');
                            } elseif ($role->name == 'Patient') {
                                return __('messages.role.patient');
                            } elseif ($role->name == 'Lab Technician') {
                                return __('messages.role.lab_technician');
                            }
                        }
                    })
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->label(__('messages.employee_payroll.role')),
                TextColumn::make('email')
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->sortable()
                    ->label(__('messages.user.email')),
                PhoneColumn::make('phone')
                    ->label(__('messages.user.phone'))
                    ->default(__('messages.common.n/a'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        if ($record->status == 1) {
                            return __('messages.common.active');
                        } else {
                            return __('messages.common.deactive');
                        }
                    })
                    ->color(function ($record) {
                        if ($record->status == 1) {
                            return 'success';
                        } else {
                            return 'danger';
                        }
                    })
                    ->label(__('messages.user.status')),
                TextColumn::make('created_at')
                    ->label(__('messages.common.created_at'))
                    ->html()
                    ->sortable()
                    ->searchable()
                    ->extraAttributes([
                        'class' => 'text-center',
                    ])
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        return '<span class="text-xs">'.\Carbon\Carbon::parse($record->created_at)->isoFormat('LT').'<br>'.\Carbon\Carbon::parse($record->created_at)->format('d-m-Y').'</span>';
                    }),
            ])->actionsColumnLabel(__('messages.impersonate'))->actions([
                Impersonate::make()
                    ->redirectTo(function (User $record) {
                        if ($record->hasRole('Admin')) {
                            return route('filament.hospitalAdmin.pages.dashboard');
                        } elseif ($record->hasRole('Doctor')) {
                            return route('filament.hospitalAdmin.reports.resources.birth-reports.index');
                        } elseif ($record->hasRole('Patient')) {
                            return route('filament.hospitalAdmin.pages.dashboard');
                        } elseif ($record->hasRole('Nurse')) {
                            return route('filament.hospitalAdmin.bed-management');
                        } elseif ($record->hasRole('Receptionist')) {
                            return route('filament.hospitalAdmin.patients');
                        } elseif ($record->hasRole('Pharmacist')) {
                            return route('filament.hospitalAdmin.medicine');
                        } elseif ($record->hasRole('Accountant')) {
                            return route('filament.hospitalAdmin.finance.resources.incomes.index');
                        } elseif ($record->hasRole('Case Manager')) {
                            return route('filament.hospitalAdmin.doctors');
                        } elseif ($record->hasRole('Lab Technician')) {
                            return route('filament.hospitalAdmin.doctors.resources.doctors.index');
                        } else {
                            return route('filament.hospitalAdmin.pages.dashboard');
                        }
                    })
                    ->color(function (User $record) {
                        if ($record->email_verified_at == null) {
                            return 'secondary';
                        }
                    })
                    ->disabled(function (User $record) {
                        if (
                            $record->email_verified_at == null || ! $record->hasRole(['Admin', 'Doctor', 'Patient', 'Nurse', 'Receptionist', 'Pharmacist', 'Accountant', 'Case Manager', 'Lab Technician'])
                        ) {
                            return true;
                        }

                        return false;
                    })
                    ->label(__('messages.impersonate')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('messages.user.status').':')
                    ->searchable()
                    ->preload()
                    ->options([
                        '' => __('messages.filter.all'),
                        '1' => __('messages.filter.active'),
                        '0' => __('messages.filter.deactive'),
                    ])->native(false),
                SelectFilter::make('roles')
                    ->preload()
                    ->searchable()
                    ->label(__('messages.employee_payroll.role').':')
                    ->native(false)
                    ->relationship('roles', 'name'),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public function render()
    {
        return view('livewire.hospital-user-table');
    }
}
