<?php

namespace App\Filament\Clusters\Billings\Resources\TransactionResource\Pages;

use App\Filament\Clusters\Billings\Resources\TransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
}
