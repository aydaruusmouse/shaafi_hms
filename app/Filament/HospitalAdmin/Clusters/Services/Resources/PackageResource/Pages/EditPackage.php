<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\PackageResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\PackageResource;
use App\Models\PackageService;
use App\Repositories\PackageRepository;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EditPackage extends EditRecord
{
    protected static string $resource = PackageResource::class;

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
        getUniqueNameValidation(static::getModel(), $this->record, $this->data, $this, isEdit: true, isPage: true, error: __('messages.package.package'));
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $packageItems = PackageService::where('package_id', $data['id'])->get();
        // dd($packageItems);
        $formattedData = [];

        foreach ($packageItems as $item) {
            $formattedData['package'][] = [
                'service_id' => $item->service_id,
                'rate' => $item->rate,
                'quantity' => $item->quantity,
                'amount' => $item->amount,
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
        $service_id = [];
        $rate = [];
        $quantity = [];

        foreach ($input['package'] as $item) {
            $service_id[] = $item['service_id'];
            $rate[] = $item['rate'];
            $quantity[] = $item['quantity'];
        }

        // Combine data into the input array
        $input = [
            ...$input,
            'service_id' => $service_id,
            'rate' => $rate,
            'quantity' => $quantity,
        ];

        $input = Arr::except($input, ['package']);

        try {
            DB::beginTransaction();
            $package = app(PackageRepository::class)->updatePackage($record->id, $input);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            // return $this->sendError($e->getMessage());
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }

        return $package;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.package_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
