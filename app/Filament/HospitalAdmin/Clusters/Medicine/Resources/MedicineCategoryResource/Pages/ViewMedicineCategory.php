<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineCategoryResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineCategoryResource;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineCategoryResource\Widgets\MedicineList;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicineCategory extends ViewRecord
{
    protected static string $resource = MedicineCategoryResource::class;

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
                        ->label(__('messages.medicine.category')),
                    TextEntry::make('is_active')
                        ->badge()
                        ->label(__('messages.common.status'))
                        ->color(fn ($record) => $record->is_active == 1 ? 'success' : 'danger')
                        ->formatStateUsing(function ($state) {
                            return $state === 1 ? __('messages.common.active') : __('messages.common.inactive');
                        }),
                    TextEntry::make('created_at')
                        ->label(__('messages.common.created_on').':')
                        ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a')),
                    TextEntry::make('updated_at')
                        ->label(__('messages.common.last_updated').':')
                        ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a')),
                ])->columns(2),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            MedicineList::class,
        ];
    }
}
