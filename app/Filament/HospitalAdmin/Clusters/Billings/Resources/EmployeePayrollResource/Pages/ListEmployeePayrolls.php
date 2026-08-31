<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\EmployeePayrollResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\EmployeePayrollResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployeePayrolls extends ListRecords
{
    protected static string $resource = EmployeePayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
