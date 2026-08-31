<?php

namespace App\Filament\HospitalAdmin\Clusters\Enquiries\Resources\EnquiriesResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Enquiries\Resources\EnquiriesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEnquiries extends ListRecords
{
    protected static string $resource = EnquiriesResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make(),
    //     ];
    // }
}
