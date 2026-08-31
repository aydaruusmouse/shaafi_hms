<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBrandResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBrandResource;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBrandResource\Widgets\Medicine;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewMedicineBrand extends ViewRecord
{
    protected static string $resource = MedicineBrandResource::class;

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
                        ->label(__('messages.medicine.brand')),
                    TextEntry::make('email')
                        ->label(__('messages.user.email'))
                        ->getStateUsing(fn ($record) => $record->email ?? __('messages.common.n/a')),
                    TextEntry::make('phone')
                        ->label(__('messages.user.phone'))
                        ->getStateUsing(fn ($record) => $record->phone ?? __('messages.common.n/a')),

                    TextEntry::make('created_at')
                        ->label(__('messages.common.created_on'))
                        ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a')),
                    TextEntry::make('updated_at')
                        ->label(__('messages.common.last_updated'))
                        ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a')),
                ])->columns(2),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            Medicine::class,
        ];
    }
}
