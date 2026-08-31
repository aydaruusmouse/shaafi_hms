<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\EmployeePayrollResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\EmployeePayrollResource;
use App\Repositories\EmployeePayrollRepository;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmployeePayroll extends CreateRecord
{
    protected static string $resource = EmployeePayrollResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function handleRecordCreation(array $input): Model
    {
        $employeePayroll = app(EmployeePayrollRepository::class)->create($input);
        app(EmployeePayrollRepository::class)->createNotification($input);

        return $employeePayroll;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.employee_payroll_saved');
    }
}
