<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\InsuranceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\InsuranceResource;
use App\Models\InsuranceDisease;
use App\Repositories\InsuranceRepository;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditInsurance extends EditRecord
{
    protected static string $resource = InsuranceResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function beforeSave()
    {
        getUniqueNameValidation(static::getModel(), $this->record, $this->data, $this, isEdit: true, isPage: true, error: __('messages.insurance.insurance'));
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.insurance_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        $invoiceItems = InsuranceDisease::where('insurance_id', $data['id'])->get();

        $formattedData = [];

        foreach ($invoiceItems as $item) {
            $formattedData['disease_details'][] = [
                'disease_name' => $item->disease_name,
                'disease_charge' => $item->disease_charge,
            ];
        }

        $data += $formattedData;

        return $data;
    }

    public function prepareInputForInvoiceItem(array $input): array
    {
        $items = [];
        foreach ($input as $key => $data) {
            foreach ($data as $index => $value) {
                $items[$index][$key] = $value;
                if (! (isset($items[$index]['price']) && $key == 'price')) {
                    continue;
                }
                $items[$index]['price'] = removeCommaFromNumbers($items[$index]['price']);
            }
        }

        return $items;
    }

    protected function handleRecordUpdate(Model $record, array $input): Model
    {

        $insurance = $record;

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
            $insurance = app(InsuranceRepository::class)->update($insurance, $input);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            // return $this->sendError($e->getMessage());

            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
        $record = new ($this->getModel())($input);

        return $record;
    }
}
