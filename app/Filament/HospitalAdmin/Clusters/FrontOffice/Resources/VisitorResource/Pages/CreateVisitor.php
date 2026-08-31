<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\VisitorResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\VisitorResource;
use App\Models\Notification;
use App\Models\Receptionist;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateVisitor extends CreateRecord
{
    protected static string $resource = VisitorResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function afterCreate(): void
    {
        $receptionists = Receptionist::pluck('user_id', 'id')->toArray();
        $userIds = [];
        foreach ($receptionists as $key => $userId) {
            $userIds[$userId] = Notification::NOTIFICATION_FOR[Notification::RECEPTIONIST];
        }
        $users = getAllNotificationUser($userIds);

        foreach ($users as $key => $notification) {
            addNotification([
                Notification::NOTIFICATION_TYPE['Visitor'],
                $key,
                $notification,
                'New visitor added.',
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.visitor_saved');
    }
}
