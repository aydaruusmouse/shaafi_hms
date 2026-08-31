<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedAssignResource\Pages;

use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedAssignResource;
use App\Repositories\BedAssignRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CreateBedAssign extends CreateRecord
{
    protected static string $resource = BedAssignResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(BedAssignRepository::class)->store($data);
        } catch (UnprocessableEntityHttpException $e) {
            Notification::make()
                ->danger()
                ->title($e->getMessage())
                ->send();

            $this->halt();

            throw $e;
        }
    }

    protected function afterCreate(): void
    {
        app(BedAssignRepository::class)->createNotification($this->record->toArray());
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.common.bed_assigned_successfully');
    }
}
