<?php

namespace App\Filament\Clusters\LandingCMS\Resources\ServiceSliderResource\Pages;

use App\Filament\Clusters\LandingCMS\Resources\ServiceSliderResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageServiceSliders extends ManageRecords
{
    protected static string $resource = ServiceSliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('messages.service_slider.add_service_slider'))
                ->createAnother(false)
                ->modalWidth('md')
                ->modalHeading(__('messages.service_slider.add_service_slider'))
                ->successNotificationTitle(__('messages.new_change.service_slider_store')),
        ];
    }
}
