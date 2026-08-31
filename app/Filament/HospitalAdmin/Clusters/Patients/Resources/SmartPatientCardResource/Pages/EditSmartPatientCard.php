<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\SmartPatientCardResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\SmartPatientCardResource;
use Filament\Pages\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditSmartPatientCard extends EditRecord
{
    protected static string $resource = SmartPatientCardResource::class;

    public function mount(int|string $record): void
    {
        if (url()->full() == static::getResource()::getUrl('edit', ['record' => $record]).'?record=1') {
            $this->js("window.location.href = '".static::getResource()::getUrl('edit', ['record' => $record])."'");
        }
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.lunch_break.smart_card_template_update');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
