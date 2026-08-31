<?php

namespace App\Filament\Resources\SuperAdminEnquiryResource\Pages;

use App\Filament\Resources\SuperAdminEnquiryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSuperAdminEnquiry extends EditRecord
{
    protected static string $resource = SuperAdminEnquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
