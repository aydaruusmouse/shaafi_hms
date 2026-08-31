<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedResource\Pages;

use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;

class ManageBeds extends ManageRecords
{
    protected static string $resource = BedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('messages.bed.bed'))->modalHeading(__('messages.bed.bed'))->modalWidth('md')->createAnother(false)->successNotificationTitle(__('messages.common.bed_assigned_successfully')),
            Action::make('bulkBedAdd')->label(__('messages.bed.new_bulk_bed'))->url(route('filament.hospitalAdmin.bed-management.pages.bulk-bed-addition')),
        ];
    }
}
