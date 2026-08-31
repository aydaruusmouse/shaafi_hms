<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\PostalResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\PostalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPostal extends EditRecord
{
    protected static string $resource = PostalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
