<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource;
use App\Models\MedicineBill;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditMedicineBills extends EditRecord
{
    protected static string $resource = MedicineBillsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $medicineBill = MedicineBill::with('saleMedicine.medicine.category')->find($data['id']);
        if ($medicineBill) {
            $saleMedicine = $medicineBill->saleMedicine->first();
            if ($saleMedicine && $saleMedicine->medicine && $saleMedicine->medicine->category) {
                $data['category_id'] = $saleMedicine->medicine->category->id;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.medicine_bills.medicine_bill').' '.__('messages.common.saved_successfully');
    }
}
