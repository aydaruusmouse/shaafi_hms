<?php

namespace App\Filament\HospitalAdmin\Clusters\Odontogram\Resources\OdontogramResource\Pages;

use Filament\Actions;
use App\Models\Odontogram;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\HospitalAdmin\Clusters\Odontogram\Resources\OdontogramResource;

class ManageOdontograms extends ManageRecords
{
    protected static string $resource = OdontogramResource::class;
    public $odontogram = '';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function($data){
                return Odontogram::create($data);
                })
                ->createAnother(false)
                ->successNotificationTitle(__('messages.flash.odontogram_created')),
        ];
    }
}