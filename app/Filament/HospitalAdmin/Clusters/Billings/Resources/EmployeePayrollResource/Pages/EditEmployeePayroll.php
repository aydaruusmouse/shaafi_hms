<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\EmployeePayrollResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\EmployeePayrollResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditEmployeePayroll extends EditRecord
{
    protected static string $resource = EmployeePayrollResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.employee_payroll_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
