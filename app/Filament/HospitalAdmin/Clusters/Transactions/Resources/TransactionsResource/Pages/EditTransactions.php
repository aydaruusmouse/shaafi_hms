<?php

namespace App\Filament\HospitalAdmin\Clusters\Transactions\Resources\TransactionsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Transactions\Resources\TransactionsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransactions extends EditRecord
{
    protected static string $resource = TransactionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
