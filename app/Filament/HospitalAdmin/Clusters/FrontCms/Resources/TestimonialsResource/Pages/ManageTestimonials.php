<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontCms\Resources\TestimonialsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontCms\Resources\TestimonialsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTestimonials extends ManageRecords
{
    protected static string $resource = TestimonialsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('messages.testimonial.new_testimonial'))->modalWidth('md')->createAnother(false)->modalHeading(__('messages.testimonial.new_testimonial'))->successNotificationTitle(__('messages.flash.testimonial_save')),
        ];
    }
}
