<?php

namespace App\Filament\Clusters\Billings\Resources\TransactionResource\Pages;

use App\Filament\Clusters\Billings\Resources\TransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
