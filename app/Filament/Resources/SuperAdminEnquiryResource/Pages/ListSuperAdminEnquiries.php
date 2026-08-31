<?php

namespace App\Filament\Resources\SuperAdminEnquiryResource\Pages;

use App\Filament\Resources\SuperAdminEnquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuperAdminEnquiries extends ListRecords
{
    protected static string $resource = SuperAdminEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
