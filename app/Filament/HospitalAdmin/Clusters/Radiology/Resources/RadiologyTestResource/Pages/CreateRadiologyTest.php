<?php

namespace App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyTestResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyTestResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRadiologyTest extends CreateRecord
{
    protected static string $resource = RadiologyTestResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['status'] ?? 0) == 1) {
            $data['uploaded_at'] = now();
        }
        if (empty($data['doctor_id']) && getLoggedinDoctor()) {
            $data['doctor_id'] = auth()->user()->owner_id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    // protected function handleRecordCreation(array $input): Model
    // {
    //     dd($input);
    // }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.radiology_test_saved');
    }
}
