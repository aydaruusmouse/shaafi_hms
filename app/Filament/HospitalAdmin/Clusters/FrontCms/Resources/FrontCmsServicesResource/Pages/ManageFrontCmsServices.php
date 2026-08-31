<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontCms\Resources\FrontCmsServicesResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontCms\Resources\FrontCmsServicesResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFrontCmsServices extends ManageRecords
{
    protected static string $resource = FrontCmsServicesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('messages.front_services.new_service'))->modalWidth('md')->createAnother(false)->modalHeading(__('messages.front_services.new_service'))->successNotificationTitle(__('messages.flash.frontService_saved')),
        ];
    }
}
