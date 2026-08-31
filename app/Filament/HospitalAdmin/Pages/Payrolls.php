<?php

namespace App\Filament\HospitalAdmin\Pages;

use App\Models\Accountant;
use App\Models\CaseHandler;
use App\Models\Doctor;
use App\Models\EmployeePayroll;
use App\Models\LabTechnician;
use App\Models\Nurse;
use App\Models\Pharmacist;
use App\Models\Receptionist;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class Payrolls extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 18;

    protected static ?string $navigationIcon = 'fas-chart-pie';

    protected static string $view = 'filament.hospital-admin.pages.payrolls';

    public static function canAccess(): bool
    {
        if (auth()->user()->hasRole(['Doctor', 'Accountant', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse']) && ! getModuleAccess('My Payrolls')) {
            return false;
        }

        return ! auth()->user()->hasRole(['Admin', 'Patient']);
    }

    public function getHeaderActions(): array
    {
        return [
            ExportAction::make()->icon('')
                ->disabled(! $this->getTableQuery()->exists())
                ->label(__('messages.common.export_to_excel'))->exports([
                    ExcelExport::make()
                        ->withFilename(__('messages.nurses').'-'.now()->format('Y-m-d').'.xlsx')
                        ->modifyQueryUsing(function (Builder $query) {
                            return $query->where('tenant_id', auth()->user()->tenant_id);
                        })
                        ->withColumns([
                            Column::make('id')->heading('No')->formatStateUsing(function () {
                                return $this->i++;
                            }),
                            Column::make('sr_no')->heading(heading: __('messages.common.name'))
                                ->formatStateUsing(fn ($record) => $record->user->full_name ?? __('messages.common.n/a')),
                            Column::make('payroll_id')->heading(heading: __('messages.user.email')),

                        ]),

                ]),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = EmployeePayroll::whereHasMorph(
            'owner',
            [
                Nurse::class,
                Doctor::class,
                LabTechnician::class,
                Receptionist::class,
                Pharmacist::class,
                Accountant::class,
                CaseHandler::class,
            ],
            function ($q, $type) {
                if (in_array($type, EmployeePayroll::PYAYROLLUSERS)) {
                    if ($type == \App\Models\Doctor::class) {
                        $q->whereHas('doctorUser', function (Builder $qr) {
                            return $qr;
                        });
                    } else {
                        $q->whereHas('user', function (Builder $qr) {
                            return $qr;
                        });
                    }
                }
            }
        )->with('owner')->select('employee_payrolls.*');

        $user = Auth::user();
        $route = Route::current()->getName();
        if (! ($route == 'payroll' && ! $user->hasRole(['Admin']))) {
            $query->where('owner_id', $user->owner_id);
            $query->where('owner_type', $user->owner_type);

            return $query;
        }
        $query->where('owner_id', $user->owner_id);
        $query->where('owner_type', $user->owner_type);

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('payroll_id')
                    ->label(__('messages.employee_payroll.payroll_id'))
                    ->sortable()
                    ->badge()
                    ->searchable(),
                TextColumn::make('month')
                    ->label(__('messages.employee_payroll.month'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('year')
                    ->label(__('messages.employee_payroll.year'))
                    ->sortable(),
                TextColumn::make('basic_salary')
                    ->label(__('messages.employee_payroll.basic_salary'))
                    ->formatStateUsing(function (EmployeePayroll $record) {
                        return getCurrencyFormat($record->basic_salary);
                    })
                    ->sortable(),
                TextColumn::make('allowance')
                    ->label(__('messages.employee_payroll.allowance'))
                    ->formatStateUsing(function (EmployeePayroll $record) {
                        return getCurrencyFormat($record->allowance);
                    })
                    ->sortable(),
                TextColumn::make('deductions')
                    ->label(__('messages.employee_payroll.deductions'))
                    ->formatStateUsing(function (EmployeePayroll $record) {
                        return getCurrencyFormat($record->deductions);
                    })
                    ->sortable(),
                TextColumn::make('net_salary')
                    ->label(__('messages.employee_payroll.net_salary'))
                    ->formatStateUsing(function (EmployeePayroll $record) {
                        return getCurrencyFormat($record->net_salary);
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('messages.common.status'))
                    ->formatStateUsing(function (EmployeePayroll $record) {
                        if ($record->status == 1) {
                            return __('messages.employee_payroll.paid');
                        } else {
                            return __('messages.employee_payroll.unpaid');
                        }
                    })
                    ->color(fn (EmployeePayroll $record) => $record->status == 1 ? 'success' : 'danger')
                    ->sortable(),
            ])
            ->recordUrl(null);
    }
}
