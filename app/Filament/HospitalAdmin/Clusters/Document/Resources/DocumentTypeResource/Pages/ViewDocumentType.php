<?php

namespace App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentTypeResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentTypeResource;
use App\Filament\HospitalAdmin\Clusters\Document\Resources\DocumentTypeResource\Widgets\DocumentTypeList;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentType extends ViewRecord
{
    protected static string $resource = DocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->modalWidth('md')->successNotificationTitle(__('messages.flash.document_type_updated')),
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(url()->previous()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('')
                ->schema([
                    TextEntry::make('name')
                        ->label(__('messages.document.document_type').':'),
                    TextEntry::make('created_at')
                        ->label(__('messages.common.created_on').':')
                        ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a')),
                    TextEntry::make('updated_at')
                        ->label(__('messages.common.last_updated').':')
                        ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a')),
                ])->columns(3),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            DocumentTypeList::class,
        ];
    }
}
