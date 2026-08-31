<?php

namespace App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources\ChargeTypeResource\Pages;

use App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources\ChargeTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageChargeTypes extends ManageRecords
{
    protected static string $resource = ChargeTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->createAnother(false)
                ->mutateFormDataUsing(function (array $data): array {
                    $data['tenant_id'] = auth()->user()->tenant_id;

                    return $data;
                })
                ->successNotificationTitle(__('messages.charge_type.saved')),
        ];
    }
}
