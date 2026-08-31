<?php

namespace App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentTypeResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentTypeResource;
use App\Models\DocumentType;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListDocumentTypes extends ListRecords
{
    protected static string $resource = DocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('messages.doc_type.new_doc_type'))->modalWidth('md')->createAnother(false)->modalHeading(__('messages.document.new_document'))->successNotificationTitle(__('messages.flash.document_type_saved'))
                ->using(function ($record, $data) {
                    $foundType = DocumentType::where('name', $data['name'])->whereTenantId(getLoggedInUser()->tenant_id)->first();

                    if ($foundType) {
                        Notification::make()
                            ->danger()
                            ->title(__('validation.unique', ['attribute' => __('messages.document.document_type')]))
                            ->send();
                        $this->halt();

                        return;
                    } else {
                        DocumentType::create($data);
                    }
                }),
        ];
    }
}
