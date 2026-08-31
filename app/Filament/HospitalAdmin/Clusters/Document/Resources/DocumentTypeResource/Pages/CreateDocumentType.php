<?php

namespace App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentTypeResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentTypeResource;
use App\Repositories\DocumentTypeRepository;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDocumentType extends CreateRecord
{
    protected static string $resource = DocumentTypeResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    public function handleRecordCreation(array $input): Model
    {
        $documentType = app(DocumentTypeRepository::class)->store($input);

        return $documentType;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.document_saved');
    }
}
