<?php

namespace App\Filament\Clusters\LandingCMS\Resources\AdminTestimonialResource\Pages;

use App\Filament\Clusters\LandingCMS\Resources\AdminTestimonialResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAdminTestimonials extends ManageRecords
{
    protected static string $resource = AdminTestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('messages.testimonial.new_testimonial'))
                ->modalWidth('md')
                ->createAnother(false)
                ->modalHeading(__('messages.testimonial.new_testimonial'))
                ->successNotificationTitle(__('messages.flash.testimonial_save')),
        ];
    }
}
