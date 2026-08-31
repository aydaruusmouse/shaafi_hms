<?php

namespace App\Filament\Resources\AdminResource\Pages;

use App\Filament\Resources\AdminResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdmins extends ListRecords
{
    public function mount(): void
    {
        $previousUrl = url()->previous();

        if (str()->endsWith($previousUrl, '/edit')) {
            $this->js('window.location.reload()');
        }
    }

    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
