<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\InsuranceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\InsuranceResource;
use App\Repositories\InsuranceRepository;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateInsurance extends CreateRecord
{
    protected static string $resource = InsuranceResource::class;

    protected static bool $canCreateAnother = false;

    protected function beforeCreate()
    {
        getUniqueNameValidation(static::getModel(), null, $this->data, $this, isEdit: false, isPage: true, error: __('messages.insurance.insurance'));
    }

    protected function handleRecordCreation(array $input): Model
    {
        $disease_name = [];
        $disease_charge = [];

        foreach ($input['disease_details'] as $item) {
            $disease_name[] = $item['disease_name'];
            $disease_charge[] = $item['disease_charge'];
        }

        $input = [
            ...$input,
            'disease_name' => $disease_name,
            'disease_charge' => $disease_charge,
        ];
        $input = Arr::except($input, ['disease_details']);

        try {
            DB::beginTransaction();
            app(InsuranceRepository::class)->store($input);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
            // return $this->sendError($e->getMessage());

            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }

        $record = new ($this->getModel())($input);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.insurance_saved');
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
