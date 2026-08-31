<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\SmartPatientCardResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\SmartPatientCardResource;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSmartPatientCard extends CreateRecord
{
    protected static string $resource = SmartPatientCardResource::class;

    protected static bool $canCreateAnother = false;

    // public function mount(): void
    // {
    //     if (url()->full() == static::getResource()::getUrl('create') . '?record=1') {
    //         $this->js("window.location.href = '" . static::getResource()::getUrl('create') . "'");
    //     }
    // }

    protected function handleRecordCreation(array $data): Model
    {
        $data['show_email'] = $data['show_email'] ?? 0;
        $data['show_phone'] = $data['show_phone'] ?? 0;
        $data['show_dob'] = $data['show_dob'] ?? 0;
        $data['show_blood_group'] = $data['show_blood_group'] ?? 0;
        $data['show_address'] = $data['show_address'] ?? 0;
        $data['show_patient_unique_id'] = $data['show_patient_unique_id'] ?? 0;

        if (empty($data['header_color'])) {
            Notification::make()->warning()->title(__('messages.flash.please_select_header_color'))->send();
            $this->halt();
        }

        return parent::handleRecordCreation($data);
    }

    protected function getActions(): array
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

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.lunch_break.smart_card_template_saved');
    }
}
