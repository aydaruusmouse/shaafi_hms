<?php

namespace App\Filament\Clusters\LandingCMS\Resources\FaqsResource\Pages;

use App\Filament\Clusters\LandingCMS\Resources\FaqsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFaqs extends ManageRecords
{
    protected static string $resource = FaqsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('messages.faqs.add_faqs'))
                ->modalWidth('md')
                ->createAnother(false)
                ->successNotificationTitle(__('messages.flash.FAQs_created')),
        ];
    }
}
