<?php

namespace App\Filament\HospitalAdmin\Clusters\Enquiries\Resources\EnquiriesResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Enquiries\Resources\EnquiriesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEnquiries extends EditRecord
{
    protected static string $resource = EnquiriesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
