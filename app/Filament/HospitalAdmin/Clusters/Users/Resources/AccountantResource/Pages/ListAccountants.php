<?php

namespace App\Filament\HospitalAdmin\Clusters\Users\Resources\AccountantResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Users\Resources\AccountantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAccountants extends ListRecords
{
    protected static string $resource = AccountantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
