<?php

namespace App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentResource;
use App\Repositories\DocumentRepository;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

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
        if (auth()->user()->hasRole('Patient')) {
            $input['patient_id'] = getLoggedInUserId();
        }
        $document = app(DocumentRepository::class)->store($input);

        return $document;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
