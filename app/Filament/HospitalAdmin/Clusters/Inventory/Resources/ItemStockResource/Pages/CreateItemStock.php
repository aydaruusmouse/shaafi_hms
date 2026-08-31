<?php

namespace App\Filament\HospitalAdmin\Clusters\Inventory\Resources\ItemStockResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Inventory\Resources\ItemStockResource;
use App\Repositories\ItemStockRepository;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateItemStock extends CreateRecord
{
    protected static string $resource = ItemStockResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function handleRecordCreation(array $input): Model
    {
        $input['purchase_price'] = removeCommaFromNumbers($input['purchase_price']);
        app(ItemStockRepository::class)->store($input);

        $record = new ($this->getModel())($input);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.item_stock_saved');
    }
}
