<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedTypeResource\Pages;

use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedTypeResource;
use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedTypeResource\Widgets\BedTypeList;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBedType extends ViewRecord
{
    protected static string $resource = BedTypeResource::class;

    protected static ?string $title = null;

    public function getTitle(): string
    {
        return __('messages.bed_type.bed_type_details');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->successNotificationTitle(__('messages.flash.bed_type_updated')),
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
                    TextEntry::make('title')
                        ->label(__('messages.bed.bed_type').':'),
                    TextEntry::make('description')
                        ->label(__('messages.bed_type.description').':')
                        ->getStateUsing(fn ($record) => $record->description ?? __('messages.common.n/a')),
                    TextEntry::make('created_at')
                        ->label(__('messages.common.created_on').':')
                        ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a')),
                    TextEntry::make('updated_at')
                        ->label(__('messages.common.updated_at').':')
                        ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a')),
                ])->columns(2),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            BedTypeList::class,
        ];
    }
}
