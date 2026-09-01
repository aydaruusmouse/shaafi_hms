<?php

namespace App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyTestResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyTestResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditRadiologyTest extends EditRecord
{
    protected static string $resource = RadiologyTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! canAccessRecord($record, $record->id)) {
            Notification::make()
                ->danger()
                ->title(__('messages.flash.not_allow_access_record'))
                ->send();

            return $record;
        }

        $data = RadiologyTestResource::normalizeDocumentData($data);

        if (($data['status'] ?? 0) == 1) {
            $data['uploaded_at'] = $data['uploaded_at'] ?? $record->uploaded_at ?? now();
        } elseif (! empty($data['document_path'])) {
            $data['uploaded_at'] = $data['uploaded_at'] ?? $record->uploaded_at ?? now();
        } else {
            $data['uploaded_at'] = null;
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.radiology_test_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
