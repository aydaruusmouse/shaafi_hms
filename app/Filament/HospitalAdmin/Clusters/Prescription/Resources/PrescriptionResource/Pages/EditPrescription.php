<?php

namespace App\Filament\HospitalAdmin\Clusters\Prescription\Resources\PrescriptionResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Prescription\Resources\PrescriptionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditPrescription extends EditRecord
{
    protected static string $resource = PrescriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.prescription_updated');
    }
}
