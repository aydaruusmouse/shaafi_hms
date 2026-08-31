<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedResource\Pages;

use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedResource;
use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedResource\Widgets\BedAssignsList;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewBed extends ViewRecord
{
    protected static string $resource = BedResource::class;

    protected static ?string $title = null;

    public function getTitle(): string
    {
        return __('messages.bed.bed_details');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
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
                        ->label(__('messages.bed_assign.bed').':'),
                    TextEntry::make('bedType.title')
                        ->label(__('messages.bed.bed_type').':'),
                    TextEntry::make('bed_id')
                        ->label(__('messages.bed.bed_id').':'),
                    TextEntry::make('charge')
                        ->label(__('messages.bed.charge').':'),
                    TextEntry::make('is_available')
                        ->label(__('messages.bed.available'))
                        ->formatStateUsing(function ($record) {
                            return $record->is_available == 1 ? __('messages.common.yes') : __('messages.common.no');
                        })
                        ->badge()
                        ->color(function ($record) {
                            return $record->is_available == 1 ? 'success' : 'danger';
                        }),
                    TextEntry::make('created_at')
                        ->label(__('messages.common.created_on').':')
                        ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a')),
                    TextEntry::make('updated_at')
                        ->label(__('messages.common.updated_at').':')
                        ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a')),
                    TextEntry::make('description')
                        ->label(__('messages.bed_type.description').':')
                        ->getStateUsing(fn ($record) => $record->description ?? __('messages.common.n/a')),
                ])->columns(2),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            BedAssignsList::class,
        ];
    }
}
