<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\PackageResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\PackageResource;
use App\Repositories\PackageRepository;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreatePackage extends CreateRecord
{
    protected static string $resource = PackageResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.package_saved');
    }

    protected function beforeCreate()
    {
        getUniqueNameValidation(static::getModel(), null, $this->data, $this, isEdit: false, isPage: true, error: __('messages.package.package'));
    }

    protected function handleRecordCreation(array $input): Model
    {
        $service_id = [];
        $quantity = [];
        $rate = [];

        foreach ($input['package'] as $item) {
            $service_id[] = $item['service_id'];
            $quantity[] = $item['quantity'];
            $rate[] = $item['rate'];
        }

        $input = [
            ...$input,
            'service_id' => $service_id,
            'rate' => $rate,
            'quantity' => $quantity,
        ];
        $input = Arr::except($input, ['package']);

        try {
            DB::beginTransaction();
            $package = app(PackageRepository::class)->store($input);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }

        return $package;
    }

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
