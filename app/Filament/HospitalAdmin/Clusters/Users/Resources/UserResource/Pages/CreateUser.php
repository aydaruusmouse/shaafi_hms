<?php

namespace App\Filament\HospitalAdmin\Clusters\Users\Resources\UserResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Users\Resources\UserResource;
use App\Repositories\UserRepository;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function handleRecordCreation(array $input): Model
    {
        $input['hospital_name'] = $input['first_name'];
        $input['status'] = isset($input['status']) ? 1 : 0;
        $userRepository = app(UserRepository::class);
        $user = $userRepository->store($input);

        return $user ?? $this->halt();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.user_saved');
    }
}
